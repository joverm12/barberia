from django.http import HttpResponse
from django.shortcuts import render, redirect, get_object_or_404
from django.utils import timezone
from django.db.models.functions import TruncMonth
from reportlab.lib.pagesizes import letter
from reportlab.pdfgen import canvas
from django.db.models import Sum, Count
import json
from .models import (ServicioComplementario, Usuario, Empleado, Sucursal, Servicio, Pack, Cita,
                     Cliente, Pago, Factura, Postulacion, Resena,
                     ContenidoWeb, LogSistema, Notificacion)

# ══════════════════════════════════════════════
# IMPORTANTE: esta versión YA NO usa los ModelForms
# (UsuarioForm, ServicioForm, etc.) para crear/editar.
# Se escribe directo a los modelos con request.POST.get().
# Esto es lo que ya te funcionaba en crear_empleado y
# editar_complementario -- ahora TODAS las vistas siguen
# ese mismo patrón, así no hay validaciones silenciosas
# que aborten el guardado sin avisar.
# ══════════════════════════════════════════════

# ── UTILIDAD ────────────────────────────────────────────
def es_admin(request):
    return request.session.get('rol') == 'Administrador'

def registrar_log(request, accion, modulo, nivel='INFO'):
    LogSistema.objects.create(
        usuario_correo=request.session.get('correo', 'desconocido'),
        accion=accion, modulo=modulo,
        ip=request.META.get('REMOTE_ADDR'), nivel=nivel
    )

# ── AUTH ─────────────────────────────────────────────────
def login_view(request):
    if request.method == 'POST':
        correo = request.POST.get('correo', '').strip()
        contrasenia = request.POST.get('contrasenia', '').strip()
        try:
            user = Usuario.objects.get(correo=correo, contrasenia=contrasenia)
            request.session['usuario_id'] = user.id_usuario
            request.session['nombre'] = user.nombre
            request.session['rol'] = user.rol
            request.session['correo'] = user.correo
            if user.rol == 'Administrador':
                return redirect('admin_dashboard')
            return redirect('inicio')
        except Usuario.DoesNotExist:
            return render(request, 'cuentas/login.html', {'error': 'Correo o contraseña incorrectos'})
    return render(request, 'cuentas/login.html')

def logout_view(request):
    request.session.flush()
    return redirect('login')

# ── DASHBOARD PRINCIPAL ──────────────────────────────────
def admin_dashboard(request):
    if not es_admin(request):
        return redirect('login')

    usuarios   = Usuario.objects.all()
    empleados  = Empleado.objects.select_related('usuario', 'sucursal').all()
    sucursales = Sucursal.objects.all()
    servicios  = Servicio.objects.all()
    complementarios = ServicioComplementario.objects.all()
    packs      = Pack.objects.all()
    citas      = Cita.objects.select_related('cliente__usuario', 'empleado__usuario', 'servicio', 'sucursal').all().order_by('-fecha', '-hora')
    pagos      = Pago.objects.select_related('cita').all().order_by('-fecha')[:10]
    facturas   = Factura.objects.select_related('pago').all().order_by('-fecha')[:10]
    clientes_count = Cliente.objects.count()
    citas_hoy  = Cita.objects.filter(fecha=timezone.now().date()).count()
    ingresos_mes = sum(p.monto for p in Pago.objects.filter(fecha__month=timezone.now().month))
    resenas    = Resena.objects.select_related('cliente__usuario').all().order_by('-fecha')
    postulaciones = Postulacion.objects.select_related('sucursal_interes').all().order_by('-fecha_postulacion')
    contenido_inicio = ContenidoWeb.objects.filter(seccion='inicio').first()
    logs = LogSistema.objects.all().order_by('-fecha')[:50]
    
    
    ingresos_por_mes = Pago.objects.filter(fecha__year=timezone.now().year)\
        .annotate(mes=TruncMonth('fecha'))\
        .values('mes')\
        .annotate(total=Sum('monto'))\
        .order_by('mes')

    # 2. Datos para gráfico circular: Servicios más populares
    servicios_populares = Cita.objects.values('servicio__nombre')\
        .annotate(cantidad=Count('id_cita'))\
        .order_by('-cantidad')[:5]

    # Convertimos a JSON
    ingresos_data = [{'mes': i['mes'].strftime('%B'), 'total': float(i['total'])} for i in ingresos_por_mes]
    servicios_data = [{'nombre': s['servicio__nombre'], 'cantidad': s['cantidad']} for s in servicios_populares]

    # 3. UNIFICAMOS TODO EN UN SOLO CONTEXTO
    context = {
        'usuarios': usuarios, 'empleados': empleados, 'sucursales': sucursales,
        'servicios': servicios, 'complementarios': complementarios, 'packs': packs,
        'citas': citas, 'pagos': pagos, 'facturas': facturas,
        'resenas': resenas, 'postulaciones': postulaciones, 'logs': logs,
        'contenido_inicio': contenido_inicio,
        'clientes_count': clientes_count, 'citas_hoy': citas_hoy, 'ingresos_mes': ingresos_mes,
        'postulaciones_count': postulaciones.count(),
        'clientes_lista': Cliente.objects.select_related('usuario').all(),
        'sucursales_lista': sucursales,
        'servicios_lista': servicios,
        'empleados_lista': empleados,
        # AGREGAMOS LOS DATOS PARA LOS GRÁFICOS AQUÍ:
        'ingresos_json': ingresos_data,
        'servicios_json': servicios_data,
    }

    return render(request, 'admin/admin_dashboard.html', context)
    
# ── USUARIOS ─────────────────────────────────────────────
def crear_usuario(request):
    if not es_admin(request): return redirect('login')
    if request.method == 'POST':
        rol = request.POST.get('rol', 'Cliente')
        usuario = Usuario.objects.create(
            nombre=request.POST.get('nombre', ''),
            apellido=request.POST.get('apellido', ''),
            correo=request.POST.get('correo', ''),
            contrasenia=request.POST.get('contrasenia', ''),
            cedula=request.POST.get('cedula', ''),
            telefono=request.POST.get('telefono', ''),
            rol=rol,
            estado=request.POST.get('estado', 'Activo'),
        )
        if rol == 'Cliente':
            Cliente.objects.get_or_create(usuario=usuario, defaults={'telefono': request.POST.get('telefono', '')})
        registrar_log(request, f'CREATE_USUARIO [rol:{rol}]', 'M1-Admin')
    return redirect('admin_dashboard')

def editar_usuario(request, id):
    if not es_admin(request): return redirect('login')
    u = get_object_or_404(Usuario, id_usuario=id)
    if request.method == 'POST':
        u.nombre   = request.POST.get('nombre', u.nombre)
        u.apellido = request.POST.get('apellido', u.apellido)
        u.correo   = request.POST.get('correo', u.correo)
        u.cedula   = request.POST.get('cedula', u.cedula)
        u.telefono = request.POST.get('telefono', u.telefono)
        u.rol      = request.POST.get('rol', u.rol)
        u.estado   = request.POST.get('estado', u.estado)
        u.save()
        registrar_log(request, f'UPDATE_USUARIO [id:{id}]', 'M1-Admin')
    return redirect('admin_dashboard')

def eliminar_usuario(request, id):
    if not es_admin(request): return redirect('login')
    get_object_or_404(Usuario, id_usuario=id).delete()
    registrar_log(request, f'DELETE_USUARIO [id:{id}]', 'M1-Admin', 'WARNING')
    return redirect('admin_dashboard')

# ── EMPLEADOS ────────────────────────────────────────────
def crear_empleado(request):
    if not es_admin(request): return redirect('login')
    if request.method == 'POST':
        sucursal_id = request.POST.get('sucursal')
        cargo       = request.POST.get('cargo', 'Barbero')
        sucursal    = get_object_or_404(Sucursal, id_sucursal=sucursal_id)
        usuario = Usuario.objects.create(
            nombre=request.POST.get('nombre', ''),
            apellido=request.POST.get('apellido', ''),
            correo=request.POST.get('correo', ''),
            contrasenia=request.POST.get('contrasenia') or 'barber1234',
            cedula=request.POST.get('cedula', ''),
            telefono=request.POST.get('telefono', ''),
            rol=cargo,
            estado='Activo',
        )
        Empleado.objects.create(
            usuario=usuario,
            sucursal=sucursal,
            especialidad=','.join(request.POST.getlist('especialidad')),
            estado=request.POST.get('estado', 'Activo'),
            cedula=request.POST.get('cedula', ''),
            telefono=request.POST.get('telefono', ''),
            cargo=cargo,
            direccion=request.POST.get('direccion', ''),
            fecha_nacimiento=request.POST.get('fecha_nacimiento') or None,
            foto=request.FILES.get('foto'),
            hora_apertura=request.POST.get('hora_apertura') or None,
            hora_cierre=request.POST.get('hora_cierre') or None,
        )
        registrar_log(request, 'CREATE_EMPLEADO', 'M2-Ops')
    return redirect('admin_dashboard')

def editar_empleado(request, id):
    if not es_admin(request): return redirect('login')
    e = get_object_or_404(Empleado, id_empleado=id)
    if request.method == 'POST':
        e.especialidad = request.POST.get('especialidad', e.especialidad)
        e.estado       = request.POST.get('estado', e.estado)
        e.cargo        = request.POST.get('cargo', e.cargo)
        sucursal_id    = request.POST.get('sucursal')
        if sucursal_id:
            e.sucursal = get_object_or_404(Sucursal, id_sucursal=sucursal_id)
        e.save()
        e.usuario.nombre   = request.POST.get('nombre', e.usuario.nombre)
        e.usuario.apellido = request.POST.get('apellido', e.usuario.apellido)
        e.usuario.rol      = request.POST.get('cargo', e.usuario.rol)
        e.usuario.save()
        registrar_log(request, f'UPDATE_EMPLEADO [id:{id}]', 'M2-Ops')
    return redirect('admin_dashboard')

def eliminar_empleado(request, id):
    if not es_admin(request): return redirect('login')
    get_object_or_404(Empleado, id_empleado=id).delete()
    registrar_log(request, f'DELETE_EMPLEADO [id:{id}]', 'M2-Ops', 'WARNING')
    return redirect('admin_dashboard')

# ── SUCURSALES ───────────────────────────────────────────
def crear_sucursal(request):
    if not es_admin(request): return redirect('login')
    if request.method == 'POST':
        Sucursal.objects.create(
            nombre=request.POST.get('nombre', ''),
            estado=request.POST.get('estado', 'Activa'),
            direccion_completa=request.POST.get('direccion_completa', ''),
            ciudad=request.POST.get('ciudad', ''),
            provincia=request.POST.get('provincia', ''),
            telefono=request.POST.get('telefono', ''),
            correo=request.POST.get('correo', ''),
            gps_latitud=request.POST.get('gps_latitud') or None,
            gps_longitud=request.POST.get('gps_longitud') or None,
            hora_apertura=request.POST.get('hora_apertura') or '08:00',
            hora_cierre=request.POST.get('hora_cierre') or '20:00',
        )
        registrar_log(request, 'CREATE_SUCURSAL', 'M3-Cat')
    return redirect('admin_dashboard')

def editar_sucursal(request, id):
    if not es_admin(request): return redirect('login')
    s = get_object_or_404(Sucursal, id_sucursal=id)
    if request.method == 'POST':
        s.nombre             = request.POST.get('nombre', s.nombre)
        s.estado             = request.POST.get('estado', s.estado)
        s.direccion_completa = request.POST.get('direccion_completa', s.direccion_completa)
        s.ciudad             = request.POST.get('ciudad', s.ciudad)
        s.provincia          = request.POST.get('provincia', s.provincia)
        s.telefono           = request.POST.get('telefono', s.telefono)
        s.correo             = request.POST.get('correo', s.correo)
        s.hora_apertura      = request.POST.get('hora_apertura') or s.hora_apertura
        s.hora_cierre        = request.POST.get('hora_cierre') or s.hora_cierre
        s.save()
        registrar_log(request, f'UPDATE_SUCURSAL [id:{id}]', 'M3-Cat')
    return redirect('admin_dashboard')

def eliminar_sucursal(request, id):
    if not es_admin(request): return redirect('login')
    get_object_or_404(Sucursal, id_sucursal=id).delete()
    registrar_log(request, f'DELETE_SUCURSAL [id:{id}]', 'M3-Cat', 'WARNING')
    return redirect('admin_dashboard')

# ── SERVICIOS ────────────────────────────────────────────
def crear_servicio(request):
    if not es_admin(request): return redirect('login')
    if request.method == 'POST':
        Servicio.objects.create(
            nombre=request.POST.get('nombre', ''),
            categoria=request.POST.get('categoria', ''),
            estado=request.POST.get('estado', 'Activo'),
            duracion=request.POST.get('duracion') or 30,
            precio=request.POST.get('precio') or 0,
            descripcion=request.POST.get('descripcion', ''),
            foto=request.FILES.get('foto'),
        )
        registrar_log(request, 'CREATE_SERVICIO', 'M4-Cat')
    return redirect('admin_dashboard')

def editar_servicio(request, id):
    if not es_admin(request): return redirect('login')
    s = get_object_or_404(Servicio, id_servicio=id)
    if request.method == 'POST':
        s.nombre      = request.POST.get('nombre', s.nombre)
        s.categoria   = request.POST.get('categoria', s.categoria)
        s.estado      = request.POST.get('estado', s.estado)
        s.duracion    = request.POST.get('duracion') or s.duracion
        s.precio      = request.POST.get('precio') or s.precio
        s.descripcion = request.POST.get('descripcion', s.descripcion)
        if request.FILES.get('foto'):
            s.foto = request.FILES.get('foto')
        s.save()
        registrar_log(request, f'UPDATE_SERVICIO [id:{id}]', 'M4-Cat')
    return redirect('admin_dashboard')

def eliminar_servicio(request, id):
    if not es_admin(request): return redirect('login')
    get_object_or_404(Servicio, id_servicio=id).delete()
    registrar_log(request, f'DELETE_SERVICIO [id:{id}]', 'M4-Cat', 'WARNING')
    return redirect('admin_dashboard')

# ── SERVICIOS COMPLEMENTARIOS ─────────────────────────────
def crear_complementario(request):
    if not es_admin(request): return redirect('login')
    if request.method == 'POST':
        ServicioComplementario.objects.create(
            nombre=request.POST.get('nombre', ''),
            precio=request.POST.get('precio') or 0,
            duracion_extra=request.POST.get('duracion_extra') or 15,
            descripcion=request.POST.get('descripcion', ''),
            estado=request.POST.get('estado', 'Activo'),
        )
        registrar_log(request, 'CREATE_COMPLEMENTARIO', 'M4-Cat')
    return redirect('admin_dashboard')

def editar_complementario(request, id):
    if not es_admin(request): return redirect('login')
    s = get_object_or_404(ServicioComplementario, id_complementario=id)
    if request.method == 'POST':
        s.nombre         = request.POST.get('nombre', s.nombre)
        s.precio         = request.POST.get('precio') or s.precio
        s.duracion_extra = request.POST.get('duracion_extra') or s.duracion_extra
        s.descripcion    = request.POST.get('descripcion', s.descripcion)
        s.estado         = request.POST.get('estado', s.estado)
        s.save()
        registrar_log(request, f'UPDATE_COMPLEMENTARIO [id:{id}]', 'M4-Cat')
    return redirect('admin_dashboard')

def eliminar_complementario(request, id):
    if not es_admin(request): return redirect('login')
    get_object_or_404(ServicioComplementario, id_complementario=id).delete()
    registrar_log(request, f'DELETE_COMPLEMENTARIO [id:{id}]', 'M4-Cat', 'WARNING')
    return redirect('admin_dashboard')

# ── CITAS ────────────────────────────────────────────────
def crear_cita(request):
    if not es_admin(request): return redirect('login')
    if request.method == 'POST':
        cliente_id  = request.POST.get('cliente')
        sucursal_id = request.POST.get('sucursal')
        servicio_id = request.POST.get('servicio')
        empleado_id = request.POST.get('empleado')
        if not (cliente_id and sucursal_id):
            return redirect('admin_dashboard')
        cliente  = get_object_or_404(Cliente, id_cliente=cliente_id)
        sucursal = get_object_or_404(Sucursal, id_sucursal=sucursal_id)
        servicio = get_object_or_404(Servicio, id_servicio=servicio_id) if servicio_id else None
        empleado = get_object_or_404(Empleado, id_empleado=empleado_id) if empleado_id else \
                   Empleado.objects.filter(sucursal=sucursal, estado='Activo').first()
        total = servicio.precio if servicio else 0
        for cid in request.POST.getlist('comp_ids'):
            try:
                comp = ServicioComplementario.objects.get(id_complementario=cid)
                total += comp.precio
            except ServicioComplementario.DoesNotExist:
                pass
        Cita.objects.create(
            cliente=cliente, empleado=empleado, sucursal=sucursal,
            servicio=servicio, fecha=request.POST.get('fecha'),
            hora=request.POST.get('hora'),
            observaciones=request.POST.get('observaciones', ''),
            total=total,
        )
        registrar_log(request, 'CREATE_CITA', 'M5-Ops')
    return redirect('admin_dashboard')

def cambiar_estado_cita(request, id):
    if not es_admin(request): return redirect('login')
    cita = get_object_or_404(Cita, id_cita=id)
    cita.estado = request.POST.get('estado', 'Finalizada')
    cita.save()
    registrar_log(request, f'UPDATE_ESTADO_CITA [id:CT-{id:03d}]', 'M5-Citas')
    return redirect('admin_dashboard')

# ── PAGOS ────────────────────────────────────────────────
def registrar_pago(request):
    if not es_admin(request): return redirect('login')
    if request.method == 'POST':
        cita_id = request.POST.get('cita_id')
        metodo  = request.POST.get('metodo_pago', 'Efectivo')
        cita    = get_object_or_404(Cita, id_cita=cita_id)
        monto   = cita.total or 0
        pago    = Pago.objects.create(cita=cita, monto=monto, metodo_pago=metodo)
        ultimo  = Factura.objects.count() + 1
        Factura.objects.create(
            pago=pago, numero=f'FAC-{ultimo:04d}',
            fecha=timezone.now().date(), subtotal=monto, total=monto
        )
        cita.estado = 'Finalizada'
        cita.save()
        registrar_log(request, f'REGISTRAR_PAGO [cita:CT-{cita_id}]', 'M6-Caja')
    return redirect('admin_dashboard')

# ── RESEÑAS ──────────────────────────────────────────────
def aprobar_resena(request, id):
    if not es_admin(request): return redirect('login')
    r = get_object_or_404(Resena, id_resena=id)
    r.estado = 'Aprobado'; r.save()
    return redirect('admin_dashboard')

def rechazar_resena(request, id):
    if not es_admin(request): return redirect('login')
    r = get_object_or_404(Resena, id_resena=id)
    r.estado = 'Rechazado'; r.save()
    return redirect('admin_dashboard')

# ── POSTULACIONES ────────────────────────────────────────
def avanzar_postulacion(request, id):
    if not es_admin(request): return redirect('login')
    p = get_object_or_404(Postulacion, id_postulacion=id)
    etapas = ['Recibido', 'En Revisión', 'Entrevista Prog.', 'Contratado', 'Rechazado']
    idx = etapas.index(p.estado) if p.estado in etapas else 0
    if idx < len(etapas) - 2:
        p.estado = etapas[idx + 1]; p.save()
    return redirect('admin_dashboard')

def contratar_postulacion(request, id):
    if not es_admin(request): return redirect('login')
    p = get_object_or_404(Postulacion, id_postulacion=id)
    p.estado = 'Contratado'; p.save()
    return redirect('admin_dashboard')

# ── CONTENIDO WEB (lo que ve el público) ─────────────────
def guardar_contenido(request):
    if not es_admin(request): return redirect('login')
    if request.method == 'POST':
        seccion = request.POST.get('seccion', 'inicio')
        obj, _  = ContenidoWeb.objects.get_or_create(seccion=seccion)
        obj.titulo    = request.POST.get('titulo', obj.titulo)
        obj.subtitulo = request.POST.get('subtitulo', obj.subtitulo)
        if request.FILES.get('imagen'):
            obj.imagen = request.FILES.get('imagen')
        obj.save()
        registrar_log(request, f'UPDATE_CONTENIDO_WEB [{seccion}]', 'M7-Mkt')
    return redirect('admin_dashboard')

# ── NOTIFICACIONES ───────────────────────────────────────
def enviar_notificacion(request):
    if not es_admin(request): return redirect('login')
    if request.method == 'POST':
        Notificacion.objects.create(
            tipo_notificacion='Campaña',
            asunto=request.POST.get('asunto', ''),
            mensaje=request.POST.get('mensaje', ''),
            canal_difusion=','.join(request.POST.getlist('canal')) or 'correo',
        )
        registrar_log(request, 'ENVIAR_NOTIFICACION', 'M8-Mkt')
    return redirect('admin_dashboard')

def exportar_reporte_pdf(request):
    tipo = request.GET.get('tipo')
    inicio = request.GET.get('fecha_inicio')
    fin = request.GET.get('fecha_fin')
    
    response = HttpResponse(content_type='application/pdf')
    response['Content-Disposition'] = f'attachment; filename="Reporte_{tipo}.pdf"'
    
    p = canvas.Canvas(response, pagesize=letter)
    p.drawString(100, 750, f"Reporte de {tipo}")
    p.drawString(100, 730, f"Del {inicio} al {fin}")
    
    # Aquí consultarías los datos según las fechas y los dibujarías con p.drawString o tablas
    p.showPage()
    p.save()
    return response
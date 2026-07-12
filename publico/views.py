# publico/views.py — CORREGIDO COMPLETO
# ══════════════════════════════════════════════
from django.shortcuts import render, redirect, get_object_or_404
from django.http import HttpResponse
from django.utils import timezone
from cuentas.models import (Servicio, ServicioComplementario, Sucursal,
                             Postulacion, Cita, Cliente, Empleado, Pack,
                             Usuario, Pago, Factura, ContenidoWeb)
import json
from django.db import transaction
from cuentas.models import Cita
from cuentas.models import ConfiguracionSistema
from django.shortcuts import render, redirect
from django.core.files.storage import FileSystemStorage
from cuentas.models import Sucursal, Postulacion
# ── INICIO ──────────────────────────────────────────
def inicio(request):
    servicios_destacados = Servicio.objects.filter(estado='Activo')[:3]
    sucursales = Sucursal.objects.filter(estado='Activa')
    return render(request, 'publico/inicio.html', {
        'servicios': servicios_destacados,
        'sucursales': sucursales,
    })

# ── SERVICIOS ────────────────────────────────────────
def servicios(request):
    barberia        = Servicio.objects.filter(categoria='Barbería', estado='Activo')
    estetica        = Servicio.objects.filter(categoria='Estética', estado='Activo')
    packs           = Pack.objects.filter(estado='Activo')
    complementarios = ServicioComplementario.objects.filter(estado='Activo')
    return render(request, 'publico/servicios.html', {
        'barberia': barberia, 'estetica': estetica,
        'packs': packs, 'complementarios': complementarios,
    })

# ── SUCURSALES ───────────────────────────────────────
def sucursales_view(request):
    sucursales = Sucursal.objects.filter(estado='Activa')
    return render(request, 'publico/sucursales.html', {'sucursales': sucursales})

# ── TRABAJA ──────────────────────────────────────────
def trabaja(request):
    sucursales = Sucursal.objects.filter(estado='Activa')
    return render(request, 'publico/trabaja.html', {'sucursales': sucursales})

def postular(request):
    if request.method == 'POST':
        sucursal_id = request.POST.get('sucursal_interes')
        sucursal = get_object_or_404(Sucursal, id_sucursal=sucursal_id)
        Postulacion.objects.create(
            puesto_aplica=request.POST.get('puesto_aplica'),
            sucursal_interes=sucursal,
            nombre_candidato=request.POST.get('nombre_candidato'),
            correo_candidato=request.POST.get('correo_candidato'),
            telefono_contacto=request.POST.get('telefono_contacto'),
            portafolio_instagram=request.POST.get('portafolio_instagram', ''),
            motivacion=request.POST.get('motivacion', ''),
            cv_url=request.POST.get('cv_url', 'pendiente'),
        )
        sucursales = Sucursal.objects.filter(estado='Activa')
        return render(request, 'publico/trabaja.html', {
            'sucursales': sucursales, 'enviado': True,
        })
    return redirect('trabaja')

# ── AGENDAR CITA ────────────────────────────────────
def agendar_cita(request):
    if request.session.get('rol') != 'Cliente':
        request.session['next'] = '/agendar/'
        return redirect('login')

    servicios       = Servicio.objects.filter(estado='Activo')
    complementarios = ServicioComplementario.objects.filter(estado='Activo')
    sucursales      = Sucursal.objects.filter(estado='Activa')
    empleados       = Empleado.objects.filter(estado='Activo').select_related('usuario', 'sucursal')

    # IVA configurable — lo guardamos en ContenidoWeb como hack simple
    config = ConfiguracionSistema.objects.first()
    iva_porcentaje = float(config.iva_porcentaje) if config else 15.0

    return render(request, 'publico/agendar_cita.html', {
        'servicios': servicios,
        'complementarios': complementarios,
        'sucursales': sucursales,
        'empleados': empleados,
        'iva_porcentaje': iva_porcentaje,
    })

def confirmar_cita(request):
    if request.session.get('rol') != 'Cliente':
        return redirect('login')

    if request.method == 'POST':
        usuario_id  = request.session.get('usuario_id')
        usuario     = get_object_or_404(Usuario, id_usuario=usuario_id)
        cliente, _  = Cliente.objects.get_or_create(usuario=usuario)

        sucursal_id = request.POST.get('sucursal')
        servicio_id = request.POST.get('servicio')
        empleado_id = request.POST.get('empleado')
        fecha       = request.POST.get('fecha')
        hora        = request.POST.get('hora')
        observaciones = request.POST.get('observaciones', '')

        sucursal = get_object_or_404(Sucursal, id_sucursal=sucursal_id)
        servicio = get_object_or_404(Servicio, id_servicio=servicio_id) if servicio_id else None
        empleado = get_object_or_404(Empleado, id_empleado=empleado_id) if empleado_id else \
                   Empleado.objects.filter(sucursal=sucursal, estado='Activo').first()

        subtotal = float(servicio.precio) if servicio else 0
        comp_ids = request.POST.getlist('complementarios')
        for cid in comp_ids:
            try:
                comp = ServicioComplementario.objects.get(id_complementario=cid)
                subtotal += float(comp.precio)
            except ServicioComplementario.DoesNotExist:
                pass

        # IVA
        iva_obj = ContenidoWeb.objects.filter(seccion='iva').first()
        iva_pct = float(iva_obj.titulo) if iva_obj and iva_obj.titulo else 15.0
        total = round(subtotal * (1 + iva_pct / 100), 2)

        Cita.objects.create(
            cliente=cliente, empleado=empleado, sucursal=sucursal,
            servicio=servicio, fecha=fecha, hora=hora,
            observaciones=observaciones, total=total,
        )
        return redirect('perfil_cliente')   # ← redirige al perfil tras agendar

    return redirect('agendar_cita')

# ── PERFIL CLIENTE ───────────────────────────────────
def perfil_cliente(request):
    if request.session.get('rol') != 'Cliente':
        return redirect('login')

    usuario_id = request.session.get('usuario_id')
    usuario    = get_object_or_404(Usuario, id_usuario=usuario_id)
    cliente, _ = Cliente.objects.get_or_create(usuario=usuario)

    hoy      = timezone.now().date()
    proximas = Cita.objects.filter(
        cliente=cliente, fecha__gte=hoy
    ).exclude(estado__in=['Cancelada', 'Finalizada']).select_related(
        'servicio', 'sucursal', 'empleado__usuario'
    ).order_by('fecha', 'hora')

    historial = Cita.objects.filter(
        cliente=cliente, estado='Finalizada'
    ).select_related('servicio', 'sucursal').order_by('-fecha')[:10]
    

    # Actualizar sesión con datos actuales
    request.session['nombre']   = usuario.nombre
    request.session['apellido'] = usuario.apellido

    return render(request, 'publico/perfil.html', {
        'usuario':  usuario,
        'cliente':  cliente,
        'proximas': proximas,
        'historial': historial,
    })

def guardar_perfil(request):
    if request.session.get('rol') != 'Cliente':
        return redirect('login')
    if request.method == 'POST':
        usuario_id = request.session.get('usuario_id')
        usuario    = get_object_or_404(Usuario, id_usuario=usuario_id)
        usuario.nombre   = request.POST.get('nombre', usuario.nombre)
        usuario.apellido = request.POST.get('apellido', usuario.apellido)
        usuario.telefono = request.POST.get('telefono', usuario.telefono)
        usuario.save()
        request.session['nombre'] = usuario.nombre
        try:
            c = Cliente.objects.get(usuario=usuario)
            c.telefono = usuario.telefono
            c.save()
        except Cliente.DoesNotExist:
            pass
    return redirect('perfil_cliente')

def cancelar_cita(request, id):
    if request.session.get('rol') != 'Cliente':
        return redirect('login')
    usuario  = get_object_or_404(Usuario, id_usuario=request.session.get('usuario_id'))
    cliente  = get_object_or_404(Cliente, usuario=usuario)
    cita     = get_object_or_404(Cita, id_cita=id, cliente=cliente)
    if cita.estado == 'Pendiente':
        cita.estado = 'Cancelada'
        cita.save()
    return redirect('perfil_cliente')

# ── PDF COMPROBANTE CITA ────────────────────────────
def descargar_comprobante_cita(request, id):
    if request.session.get('rol') != 'Cliente':
        return redirect('login')
    usuario = get_object_or_404(Usuario, id_usuario=request.session.get('usuario_id'))
    cliente = get_object_or_404(Cliente, usuario=usuario)
    cita    = get_object_or_404(Cita, id_cita=id, cliente=cliente)

    try:
        from reportlab.pdfgen import canvas
        from reportlab.lib.pagesizes import A4
        from io import BytesIO

        buffer = BytesIO()
        p = canvas.Canvas(buffer, pagesize=A4)
        w, h = A4

        # Encabezado
        p.setFont("Helvetica-Bold", 20)
        p.drawString(50, h - 60, "BARBER HOUSE")
        p.setFont("Helvetica", 12)
        p.drawString(50, h - 80, "Comprobante de Cita")

        # Línea
        p.line(50, h - 95, w - 50, h - 95)

        # Datos
        p.setFont("Helvetica-Bold", 11)
        p.drawString(50, h - 120, f"Código de Cita:")
        p.setFont("Helvetica", 11)
        p.drawString(200, h - 120, f"CT-{cita.id_cita:03d}")

        p.setFont("Helvetica-Bold", 11)
        p.drawString(50, h - 145, "Cliente:")
        p.setFont("Helvetica", 11)
        p.drawString(200, h - 145, f"{cita.cliente.usuario.nombre} {cita.cliente.usuario.apellido}")

        p.setFont("Helvetica-Bold", 11)
        p.drawString(50, h - 170, "Servicio:")
        p.setFont("Helvetica", 11)
        p.drawString(200, h - 170, cita.servicio.nombre if cita.servicio else "—")

        p.setFont("Helvetica-Bold", 11)
        p.drawString(50, h - 195, "Sucursal:")
        p.setFont("Helvetica", 11)
        p.drawString(200, h - 195, cita.sucursal.nombre)

        p.setFont("Helvetica-Bold", 11)
        p.drawString(50, h - 220, "Fecha y Hora:")
        p.setFont("Helvetica", 11)
        p.drawString(200, h - 220, f"{cita.fecha.strftime('%d/%m/%Y')} {cita.hora.strftime('%H:%M')}")

        p.setFont("Helvetica-Bold", 11)
        p.drawString(50, h - 245, "Profesional:")
        p.setFont("Helvetica", 11)
        p.drawString(200, h - 245, f"{cita.empleado.usuario.nombre} {cita.empleado.usuario.apellido}")

        p.setFont("Helvetica-Bold", 11)
        p.drawString(50, h - 270, "Estado:")
        p.setFont("Helvetica", 11)
        p.drawString(200, h - 270, cita.estado)

        p.line(50, h - 290, w - 50, h - 290)

        p.setFont("Helvetica-Bold", 13)
        p.drawString(50, h - 315, f"TOTAL A PAGAR: ${cita.total}")

        p.setFont("Helvetica", 9)
        p.drawString(50, 50, "El pago se registrará en recepción del local. Barber House - Tu estilo, nuestra pasión.")

        p.save()
        buffer.seek(0)
        response = HttpResponse(buffer, content_type='application/pdf')
        response['Content-Disposition'] = f'attachment; filename="cita_CT{cita.id_cita:03d}.pdf"'
        return response

    except ImportError:
        return HttpResponse("Instala reportlab: pip install reportlab", status=500)

# ── AGENDA EMPLEADO ──────────────────────────────────
def agenda_empleado(request):
    rol = request.session.get('rol')
    if rol not in ['Barbero', 'Estilista', 'Recepcionista', 'Peluquero', 'Gerente Sucursal']:
        return redirect('login')

    usuario_id = request.session.get('usuario_id')
    usuario    = get_object_or_404(Usuario, id_usuario=usuario_id)

    try:
        empleado = Empleado.objects.select_related('sucursal').get(usuario=usuario)
    except Empleado.DoesNotExist:
        return render(request, 'publico/agenda_empleado.html', {
            'error': 'No tienes un perfil de empleado asignado.'
        })

    hoy   = timezone.now().date()
    citas = Cita.objects.filter(
        empleado=empleado, fecha=hoy
    ).select_related('cliente__usuario', 'servicio').order_by('hora')

    return render(request, 'publico/agenda_empleado.html', {
        'empleado': empleado,
        'citas':    citas,
        'hoy':      hoy,
    })

def iniciar_atencion(request, id):
    cita = get_object_or_404(Cita, id_cita=id)
    cita.estado = 'En Proceso'; cita.save()
    return redirect('agenda_empleado')

def finalizar_servicio(request, id):
    cita = get_object_or_404(Cita, id_cita=id)
    
    with transaction.atomic():
        cita.estado = 'Finalizada'
        cita.save()
        
        # Crear registro de pago automáticamente para que aparezca en el Dashboard
        if not Pago.objects.filter(cita=cita).exists():
            Pago.objects.create(
                cita=cita,
                monto=cita.total, 
                fecha=timezone.now(),
                metodo_pago='Efectivo' # Valor por defecto para pruebas
            )
            
    return redirect('agenda_empleado')

# ── API: EMPLEADOS POR SUCURSAL (para agendar dinámico) ──
def api_empleados_sucursal(request):
    sucursal_id = request.GET.get('sucursal_id')
    if not sucursal_id:
        return HttpResponse('[]', content_type='application/json')
    empleados = Empleado.objects.filter(
        sucursal__id_sucursal=sucursal_id, estado='Activo'
    ).select_related('usuario')
    data = [{'id': e.id_empleado, 'nombre': f"{e.usuario.nombre} {e.usuario.apellido}"} for e in empleados]
    return HttpResponse(json.dumps(data), content_type='application/json')

def postular(request):
    if request.method == 'POST':
        # 1. Procesar el archivo manualmente
        archivo = request.FILES.get('cv_file')
        cv_ruta = 'pendiente'
        if archivo:
            fs = FileSystemStorage()
            nombre_archivo = fs.save(f'cvs/{archivo.name}', archivo)
            cv_ruta = fs.url(nombre_archivo)

        # 2. Guardar en la base de datos
        Postulacion.objects.create(
            puesto_aplica=request.POST.get('puesto_aplica'),
            sucursal_interes_id=request.POST.get('sucursal_interes'),
            nombre_candidato=request.POST.get('nombre_candidato'),
            correo_candidato=request.POST.get('correo_candidato'),
            telefono_contacto=request.POST.get('telefono_contacto'),
            portafolio_instagram=request.POST.get('portafolio_instagram'),
            motivacion=request.POST.get('motivacion'),
            cv_url=cv_ruta  # Guardamos la ruta del archivo aquí
        )
        return render(request, 'publico/trabaja.html', {'enviado': True})
    
    # Si es GET, necesitamos enviar las sucursales al template
    sucursales = Sucursal.objects.all()
    return render(request, 'publico/trabaja.html', {'sucursales': sucursales})
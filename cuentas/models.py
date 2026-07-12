from django.db import models
from cloudinary.models import CloudinaryField # Importar esto

class Usuario(models.Model):
    id_usuario = models.AutoField(primary_key=True)
    nombre = models.CharField(max_length=100)
    apellido = models.CharField(max_length=100)
    correo = models.EmailField(max_length=150, unique=True)
    contrasenia = models.CharField(max_length=255)
    rol = models.CharField(max_length=30)
    estado = models.CharField(max_length=20, default='Activo')
    cedula = models.CharField(max_length=20, null=True, blank=True)
    telefono = models.CharField(max_length=20, null=True, blank=True)
    class Meta:
        db_table = 'usuario'

class Sucursal(models.Model):
    id_sucursal = models.AutoField(primary_key=True)
    nombre = models.CharField(max_length=100)
    estado = models.CharField(max_length=20, default='Activa')
    direccion_completa = models.CharField(max_length=255)
    ciudad = models.CharField(max_length=100)
    provincia = models.CharField(max_length=100)
    telefono = models.CharField(max_length=20)
    correo = models.EmailField(max_length=150)
    gps_latitud = models.DecimalField(max_digits=10, decimal_places=8, null=True, blank=True)
    gps_longitud = models.DecimalField(max_digits=11, decimal_places=8, null=True, blank=True)
    hora_apertura = models.TimeField()
    hora_cierre = models.TimeField()
    imagen = CloudinaryField('image', blank=True, null=True)
    class Meta:
        db_table = 'sucursal'

class Cliente(models.Model):
    id_cliente = models.AutoField(primary_key=True)
    usuario = models.ForeignKey(Usuario, on_delete=models.CASCADE, db_column='id_usuario')
    telefono = models.CharField(max_length=20, null=True, blank=True)
    direccion = models.CharField(max_length=255, null=True, blank=True)
    fecha_registro = models.DateField(auto_now_add=True)
    class Meta:
        db_table = 'cliente'

class Empleado(models.Model):
    id_empleado = models.AutoField(primary_key=True)
    usuario = models.ForeignKey(Usuario, on_delete=models.CASCADE, db_column='id_usuario')
    sucursal = models.ForeignKey(Sucursal, on_delete=models.CASCADE, db_column='id_sucursal')
    especialidad = models.CharField(max_length=100, null=True, blank=True)
    estado = models.CharField(max_length=20, default='Activo')
    cedula = models.CharField(max_length=20, null=True, blank=True)
    telefono = models.CharField(max_length=20, null=True, blank=True)
    fecha_nacimiento = models.DateField(null=True, blank=True)
    direccion = models.CharField(max_length=255, null=True, blank=True)
    cargo = models.CharField(max_length=100, null=True, blank=True)
    foto = models.ImageField(upload_to='empleados/', null=True, blank=True)
    hora_apertura = models.TimeField(null=True, blank=True)
    hora_cierre = models.TimeField(null=True, blank=True)
    class Meta:
        db_table = 'empleado'

class Servicio(models.Model):
    id_servicio = models.AutoField(primary_key=True)
    nombre = models.CharField(max_length=100)
    descripcion = models.CharField(max_length=255, null=True, blank=True)
    precio = models.DecimalField(max_digits=10, decimal_places=2)
    duracion = models.IntegerField()
    categoria = models.CharField(max_length=100, null=True, blank=True)
    estado = models.CharField(max_length=20, default='Activo')
    nombre = models.CharField(max_length=100)
    imagen = CloudinaryField('image', blank=True, null=True)
    class Meta:
        db_table = 'servicio'

class Pack(models.Model):
    id_pack = models.AutoField(primary_key=True)
    nombre = models.CharField(max_length=100)
    descripcion = models.CharField(max_length=255, null=True, blank=True)
    precio = models.DecimalField(max_digits=10, decimal_places=2)
    duracion = models.IntegerField(default=60)
    estado = models.CharField(max_length=20, default='Activo')
    class Meta:
        db_table = 'pack'

class Cita(models.Model):
    id_cita = models.AutoField(primary_key=True)
    cliente = models.ForeignKey(Cliente, on_delete=models.CASCADE, db_column='id_cliente')
    empleado = models.ForeignKey(Empleado, on_delete=models.CASCADE, db_column='id_empleado')
    sucursal = models.ForeignKey(Sucursal, on_delete=models.CASCADE, db_column='id_sucursal')
    servicio = models.ForeignKey(Servicio, on_delete=models.SET_NULL, null=True, blank=True, db_column='id_servicio')
    pack = models.ForeignKey(Pack, on_delete=models.SET_NULL, null=True, blank=True, db_column='id_pack')
    fecha = models.DateField()
    hora = models.TimeField()
    estado = models.CharField(max_length=30, default='Pendiente')
    observaciones = models.CharField(max_length=255, null=True, blank=True)
    total = models.DecimalField(max_digits=10, decimal_places=2, default=0)
    class Meta:
        db_table = 'cita'

class Pago(models.Model):
    id_pago = models.AutoField(primary_key=True)
    cita = models.ForeignKey(Cita, on_delete=models.CASCADE, db_column='id_cita')
    fecha = models.DateField(auto_now_add=True)
    monto = models.DecimalField(max_digits=10, decimal_places=2)
    metodo_pago = models.CharField(max_length=50)
    class Meta:
        db_table = 'pago'

class Factura(models.Model):
    id_factura = models.AutoField(primary_key=True)
    pago = models.ForeignKey(Pago, on_delete=models.CASCADE, db_column='id_pago')
    numero = models.CharField(max_length=50, unique=True)
    fecha = models.DateField(auto_now_add=True)
    subtotal = models.DecimalField(max_digits=10, decimal_places=2)
    total = models.DecimalField(max_digits=10, decimal_places=2)
    class Meta:
        db_table = 'factura'

class Notificacion(models.Model):
    id_notificacion = models.AutoField(primary_key=True)
    usuario = models.ForeignKey(Usuario, on_delete=models.SET_NULL, null=True, db_column='id_usuario')
    tipo_notificacion = models.CharField(max_length=50)
    asunto = models.CharField(max_length=150)
    mensaje = models.TextField()
    canal_difusion = models.CharField(max_length=50)
    fecha_envio = models.DateTimeField(auto_now_add=True)
    class Meta:
        db_table = 'notificacion'

class Postulacion(models.Model):
    id_postulacion = models.AutoField(primary_key=True)
    puesto_aplica = models.CharField(max_length=100)
    sucursal_interes = models.ForeignKey(Sucursal, on_delete=models.CASCADE, db_column='id_sucursal_interes')
    nombre_candidato = models.CharField(max_length=100)
    correo_candidato = models.EmailField(max_length=150)
    telefono_contacto = models.CharField(max_length=20)
    portafolio_instagram = models.CharField(max_length=150, null=True, blank=True)
    motivacion = models.TextField(null=True, blank=True)
    cv_url = models.CharField(max_length=255)
    fecha_postulacion = models.DateField(auto_now_add=True)
    estado = models.CharField(max_length=30, default='Recibido')
    class Meta:
        db_table = 'postulacion'

class Resena(models.Model):
    id_resena = models.AutoField(primary_key=True)
    cliente = models.ForeignKey(Cliente, on_delete=models.CASCADE, db_column='id_cliente')
    calificacion = models.IntegerField(default=5)
    comentario = models.TextField()
    estado = models.CharField(max_length=20, default='Pendiente')  # Pendiente, Aprobado, Rechazado
    fecha = models.DateTimeField(auto_now_add=True)
    class Meta:
        db_table = 'resena'

class ContenidoWeb(models.Model):
    id_contenido = models.AutoField(primary_key=True)
    seccion = models.CharField(max_length=50)  # inicio, nosotros, valores, etc.
    titulo = models.CharField(max_length=255, null=True, blank=True)
    subtitulo = models.TextField(null=True, blank=True)
    imagen = models.ImageField(upload_to='web/', null=True, blank=True)
    class Meta:
        db_table = 'contenido_web'

class LogSistema(models.Model):
    id_log = models.AutoField(primary_key=True)
    usuario_correo = models.CharField(max_length=150)
    accion = models.CharField(max_length=255)
    modulo = models.CharField(max_length=50)
    ip = models.CharField(max_length=50, null=True, blank=True)
    fecha = models.DateTimeField(auto_now_add=True)
    nivel = models.CharField(max_length=20, default='INFO')  # INFO, WARNING, ERROR
    class Meta:
        db_table = 'log_sistema'

# Agrega esta clase a cuentas/models.py (después de Servicio)

# Agrega esta clase a cuentas/models.py (después de Servicio)

class ServicioComplementario(models.Model):
    id_complementario = models.AutoField(primary_key=True)
    nombre = models.CharField(max_length=100)
    precio = models.DecimalField(max_digits=10, decimal_places=2)
    duracion_extra = models.IntegerField(default=15)  # minutos extra
    estado = models.CharField(max_length=20, default='Activo')
    descripcion = models.CharField(max_length=255, null=True, blank=True)

    class Meta:
        db_table = 'servicio_complementario'

    def __str__(self):
        return f"{self.nombre} (+${self.precio})"

from django.db import models

class ConfiguracionSistema(models.Model):
    iva_porcentaje = models.DecimalField(max_digits=5, decimal_places=2, default=15.00)
    
    def __str__(self):
        return f"IVA: {self.iva_porcentaje}%"
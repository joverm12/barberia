from django import forms
from .models import Usuario, Empleado, Sucursal, Servicio, Pack, Cita, Cliente, Pago, Notificacion, ContenidoWeb

class UsuarioForm(forms.ModelForm):
    class Meta:
        model = Usuario
        fields = ['nombre', 'apellido', 'correo', 'contrasenia', 'rol', 'estado', 'cedula', 'telefono']
        widgets = {
            'contrasenia': forms.PasswordInput(),
            'nombre': forms.TextInput(attrs={'placeholder': 'Ej. Juan'}),
            'apellido': forms.TextInput(attrs={'placeholder': 'Ej. Pérez'}),
            'correo': forms.EmailInput(attrs={'placeholder': 'correo@ejemplo.com'}),
            'cedula': forms.TextInput(attrs={'placeholder': 'Solo números'}),
            'telefono': forms.TextInput(attrs={'placeholder': '099...'}),
        }

class EmpleadoForm(forms.ModelForm):
    nombre = forms.CharField(max_length=100, widget=forms.TextInput(attrs={'placeholder': 'Ej. Juan Mateo'}))
    apellido = forms.CharField(max_length=100, widget=forms.TextInput(attrs={'placeholder': 'Ej. Gómez'}))
    correo = forms.EmailField(widget=forms.EmailInput(attrs={'placeholder': 'correo@empresa.com'}))
    contrasenia = forms.CharField(widget=forms.PasswordInput(), required=False)

    class Meta:
        model = Empleado
        fields = ['sucursal', 'especialidad', 'estado', 'cedula', 'telefono',
                  'fecha_nacimiento', 'direccion', 'cargo', 'foto', 'hora_apertura', 'hora_cierre']
        widgets = {
            'cedula': forms.TextInput(attrs={'placeholder': 'Solo números'}),
            'telefono': forms.TextInput(attrs={'placeholder': '099...'}),
            'fecha_nacimiento': forms.DateInput(attrs={'type': 'date'}),
            'direccion': forms.TextInput(attrs={'placeholder': 'Calle principal y transversal...'}),
            'hora_apertura': forms.TimeInput(attrs={'type': 'time', 'value': '09:00'}),
            'hora_cierre': forms.TimeInput(attrs={'type': 'time', 'value': '18:00'}),
        }

class SucursalForm(forms.ModelForm):
    class Meta:
        model = Sucursal
        fields = ['nombre', 'estado', 'direccion_completa', 'ciudad', 'provincia',
                  'telefono', 'correo', 'gps_latitud', 'gps_longitud', 'hora_apertura', 'hora_cierre']
        widgets = {
            'nombre': forms.TextInput(attrs={'placeholder': 'Ej. Barber House - Centro'}),
            'direccion_completa': forms.TextInput(attrs={'placeholder': 'Calle principal y nomenclatura...'}),
            'ciudad': forms.TextInput(attrs={'placeholder': 'Ej. Quito'}),
            'provincia': forms.TextInput(attrs={'placeholder': 'Ej. Pichincha'}),
            'telefono': forms.TextInput(attrs={'placeholder': 'Solo números'}),
            'correo': forms.EmailInput(attrs={'placeholder': 'correo@sucursal.com'}),
            'gps_latitud': forms.TextInput(attrs={'placeholder': '-0.1834'}),
            'gps_longitud': forms.TextInput(attrs={'placeholder': '-78.4832'}),
            'hora_apertura': forms.TimeInput(attrs={'type': 'time', 'value': '08:00'}),
            'hora_cierre': forms.TimeInput(attrs={'type': 'time', 'value': '20:00'}),
        }

class ServicioForm(forms.ModelForm):
    class Meta:
        model = Servicio
        fields = ['nombre', 'categoria', 'estado', 'duracion', 'precio', 'foto', 'descripcion']
        widgets = {
            'nombre': forms.TextInput(attrs={'placeholder': 'Ej. Afeitado Ritual'}),
            'duracion': forms.NumberInput(attrs={'placeholder': '45'}),
            'precio': forms.NumberInput(attrs={'placeholder': '15.00', 'step': '0.01'}),
            'descripcion': forms.Textarea(attrs={'placeholder': 'Describe el servicio...', 'rows': 3}),
            'categoria': forms.Select(choices=[
                ('Barbería', 'Barbería'),
                ('Estética', 'Estética'),
                ('Packs Promocionales', 'Packs Promocionales'),
            ]),
        }

class CitaForm(forms.ModelForm):
    class Meta:
        model = Cita
        fields = ['cliente', 'sucursal', 'servicio', 'empleado', 'fecha', 'hora', 'observaciones']
        widgets = {
            'fecha': forms.DateInput(attrs={'type': 'date'}),
            'hora': forms.TimeInput(attrs={'type': 'time'}),
            'observaciones': forms.Textarea(attrs={
                'placeholder': 'Ej. el cliente solicita que el corte sea rápido...', 'rows': 3
            }),
        }

class PagoForm(forms.ModelForm):
    class Meta:
        model = Pago
        fields = ['cita', 'monto', 'metodo_pago']
        widgets = {
            'metodo_pago': forms.Select(choices=[
                ('Efectivo', 'Efectivo'),
                ('Tarjeta', 'Tarjeta'),
                ('Transferencia', 'Transferencia'),
            ]),
        }

class NotificacionForm(forms.Form):
    asunto = forms.CharField(max_length=150, widget=forms.TextInput(attrs={'placeholder': 'Ej. Promo por el Día del Padre'}))
    segmento = forms.ChoiceField(choices=[
        ('todos', 'Todos los Clientes Registrados'),
        ('activos', 'Clientes Activos'),
        ('barberos', 'Barberos'),
    ])
    canal = forms.MultipleChoiceField(
        choices=[('correo', 'Correo Electrónico'), ('whatsapp', 'WhatsApp Business')],
        widget=forms.CheckboxSelectMultiple()
    )
    mensaje = forms.CharField(widget=forms.Textarea(attrs={'placeholder': 'Redacta tu promoción...', 'rows': 4}))

class ContenidoWebForm(forms.ModelForm):
    class Meta:
        model = ContenidoWeb
        fields = ['titulo', 'subtitulo', 'imagen']
        widgets = {
            'titulo': forms.TextInput(attrs={'placeholder': 'Elegancia y precisión en cada detalle'}),
            'subtitulo': forms.Textarea(attrs={'rows': 3, 'placeholder': 'Descripción...'}),
        }
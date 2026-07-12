"""
Comando para crear/actualizar los usuarios de prueba que pidió el jefe:

    admin@barberhouse.com    / 12345   -> Administrador
    empleado@gmail.com       / 123     -> Empleado (Barbero)
    jover@gmail.com          / 123     -> Cliente

Es seguro correrlo varias veces: si el usuario ya existe, solo actualiza
su contraseña y datos en vez de crear un duplicado (get_or_create + save).

Uso:
    python manage.py crear_usuarios_prueba
"""
from datetime import time
from django.core.management.base import BaseCommand
from cuentas.models import Usuario, Cliente, Empleado, Sucursal


class Command(BaseCommand):
    help = 'Crea/actualiza los usuarios de prueba (admin, empleado, cliente).'

    def handle(self, *args, **options):
        # ── 1. ADMINISTRADOR ────────────────────────────────
        admin, creado = Usuario.objects.get_or_create(
            correo='admin@barberhouse.com',
            defaults={
                'nombre': 'Admin',
                'apellido': 'BarberHouse',
                'contrasenia': '12345',
                'rol': 'Administrador',
                'estado': 'Activo',
            }
        )
        if not creado:
            admin.contrasenia = '12345'
            admin.rol = 'Administrador'
            admin.save()
        self.stdout.write(self.style.SUCCESS(
            f'{"Creado" if creado else "Actualizado"}: admin@barberhouse.com'
        ))

        # ── 2. SUCURSAL POR DEFECTO (el empleado necesita una) ──
        sucursal, _ = Sucursal.objects.get_or_create(
            nombre='Sucursal Principal',
            defaults={
                'estado': 'Activa',
                'direccion_completa': 'Dirección pendiente de configurar',
                'ciudad': 'Manta',
                'provincia': 'Manabí',
                'telefono': '0000000000',
                'correo': 'contacto@barberhouse.com',
                'hora_apertura': time(8, 0),
                'hora_cierre': time(20, 0),
            }
        )

        # ── 3. EMPLEADO ──────────────────────────────────────
        empleado_usuario, creado = Usuario.objects.get_or_create(
            correo='empleado@gmail.com',
            defaults={
                'nombre': 'Empleado',
                'apellido': 'Prueba',
                'contrasenia': '123',
                'rol': 'Barbero',
                'estado': 'Activo',
            }
        )
        if not creado:
            empleado_usuario.contrasenia = '123'
            empleado_usuario.rol = 'Barbero'
            empleado_usuario.save()

        Empleado.objects.get_or_create(
            usuario=empleado_usuario,
            defaults={
                'sucursal': sucursal,
                'especialidad': 'General',
                'estado': 'Activo',
                'cargo': 'Barbero',
                'hora_apertura': time(8, 0),
                'hora_cierre': time(20, 0),
            }
        )
        self.stdout.write(self.style.SUCCESS(
            f'{"Creado" if creado else "Actualizado"}: empleado@gmail.com'
        ))

        # ── 4. CLIENTE ───────────────────────────────────────
        cliente_usuario, creado = Usuario.objects.get_or_create(
            correo='jover@gmail.com',
            defaults={
                'nombre': 'Jover',
                'apellido': 'Cliente',
                'contrasenia': '123',
                'rol': 'Cliente',
                'estado': 'Activo',
            }
        )
        if not creado:
            cliente_usuario.contrasenia = '123'
            cliente_usuario.rol = 'Cliente'
            cliente_usuario.save()

        Cliente.objects.get_or_create(usuario=cliente_usuario)
        self.stdout.write(self.style.SUCCESS(
            f'{"Creado" if creado else "Actualizado"}: jover@gmail.com'
        ))

        self.stdout.write(self.style.SUCCESS('Usuarios de prueba listos.'))
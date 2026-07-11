
# publico/urls.py
from django.urls import path
from . import views

urlpatterns = [
    path('',                           views.inicio,               name='inicio'),
    path('servicios/',                 views.servicios,            name='servicios'),
    path('sucursales/',                views.sucursales_view,      name='sucursales'),
    path('trabaja-con-nosotros/',      views.trabaja,              name='trabaja'),
    path('postular/',                  views.postular,             name='postular'),
    # Agendar
    path('agendar/',                   views.agendar_cita,         name='agendar_cita'),
    path('agendar/confirmar/',         views.confirmar_cita,       name='confirmar_cita'),
    # Perfil cliente
    path('inicio/perfil/',             views.perfil_cliente,       name='perfil_cliente'),
    path('inicio/perfil/guardar/',     views.guardar_perfil,       name='guardar_perfil'),
    path('inicio/cita/cancelar/<int:id>/', views.cancelar_cita,   name='cancelar_cita'),
    path('inicio/cita/pdf/<int:id>/',  views.descargar_comprobante_cita, name='comprobante_cita'),
    # Agenda empleado
    path('inicio/agenda/',             views.agenda_empleado,      name='agenda_empleado'),
    path('inicio/agenda/iniciar/<int:id>/',   views.iniciar_atencion,   name='iniciar_atencion'),
    path('inicio/agenda/finalizar/<int:id>/', views.finalizar_servicio, name='finalizar_servicio'),
    # API
    path('api/empleados/',             views.api_empleados_sucursal, name='api_empleados'),
    
]

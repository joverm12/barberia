from django.urls import path
from . import views

urlpatterns = [
    path('', views.login_view, name='login'),
    path('logout/', views.logout_view, name='logout'),
    path('panel/dashboard/', views.admin_dashboard, name='admin_dashboard'),
    # Usuarios
    path('panel/usuario/crear/', views.crear_usuario, name='crear_usuario'),
    path('panel/usuario/editar/<int:id>/', views.editar_usuario, name='editar_usuario'),
    path('panel/usuario/eliminar/<int:id>/', views.eliminar_usuario, name='eliminar_usuario'),
    # Empleados
    path('panel/empleado/crear/', views.crear_empleado, name='crear_empleado'),
    path('panel/empleado/editar/<int:id>/', views.editar_empleado, name='editar_empleado'),
    path('panel/empleado/eliminar/<int:id>/', views.eliminar_empleado, name='eliminar_empleado'),
    # Sucursales
    path('panel/sucursal/crear/', views.crear_sucursal, name='crear_sucursal'),
    path('panel/sucursal/editar/<int:id>/', views.editar_sucursal, name='editar_sucursal'),
    path('panel/sucursal/eliminar/<int:id>/', views.eliminar_sucursal, name='eliminar_sucursal'),
    # Servicios
    path('panel/servicio/crear/', views.crear_servicio, name='crear_servicio'),
    path('panel/servicio/editar/<int:id>/', views.editar_servicio, name='editar_servicio'),
    path('panel/servicio/eliminar/<int:id>/', views.eliminar_servicio, name='eliminar_servicio'),
    # Complementarios
    path('panel/complementario/crear/', views.crear_complementario, name='crear_complementario'),
    path('panel/complementario/editar/<int:id>/', views.editar_complementario, name='editar_complementario'),
    path('panel/complementario/eliminar/<int:id>/', views.eliminar_complementario, name='eliminar_complementario'),
    # Citas, Pagos, Reseñas, etc... (Aplica el mismo cambio de 'admin/' a 'panel/' aquí)
    path('panel/cita/crear/', views.crear_cita, name='crear_cita'),
    path('panel/cita/estado/<int:id>/', views.cambiar_estado_cita, name='cambiar_estado_cita'),
    path('panel/pago/registrar/', views.registrar_pago, name='registrar_pago'),
    path('panel/resena/aprobar/<int:id>/', views.aprobar_resena, name='aprobar_resena'),
    path('panel/resena/rechazar/<int:id>/', views.rechazar_resena, name='rechazar_resena'),
    path('panel/postulacion/avanzar/<int:id>/', views.avanzar_postulacion, name='avanzar_postulacion'),
    path('panel/postulacion/contratar/<int:id>/', views.contratar_postulacion, name='contratar_postulacion'),
    path('panel/contenido/guardar/', views.guardar_contenido, name='guardar_contenido'),
    path('panel/notificacion/enviar/', views.enviar_notificacion, name='enviar_notificacion'),
    path('admin/reporte/pdf/', views.exportar_reporte_pdf, name='exportar_reporte_pdf'),
    path('panel/reportes/generar/', views.generar_reporte, name='generar_reporte'),
    path('panel/reportes/pdf/', views.descargar_pdf, name='descargar_pdf'),
    path('panel/actualizar-iva/', views.actualizar_iva, name='actualizar_iva'),

]
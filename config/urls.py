from django.contrib import admin
from django.urls import path, include
# IMPORTACIONES NECESARIAS:
from django.conf import settings
from django.conf.urls.static import static

urlpatterns = [
    path('panel-barberia/', admin.site.urls),
    path('', include('cuentas.urls')),
    path('inicio/', include('publico.urls')),
]

# ESTO VA AFUERA DE LA LISTA, AL FINAL DEL ARCHIVO:
if settings.DEBUG:
    urlpatterns += static(settings.MEDIA_URL, document_root=settings.MEDIA_ROOT)
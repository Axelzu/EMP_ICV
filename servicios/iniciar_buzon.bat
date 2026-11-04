@echo off
rem Inicia el buzon POP3 local que la impresora Ricoh consulta para recibir el pedido "requeststatus".
rem Deja un pedido nuevo cada 24 horas (1440 minutos). La impresora responde por correo y el sistema guarda el contador.
cd /d C:\xampp\htdocs\ICV\servicios
set PEDIR_CADA_MIN=1440
node pop3_local.js

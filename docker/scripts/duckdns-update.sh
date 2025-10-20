#!/bin/bash

# Configuración de DuckDNS
DOMAIN="misistema"  # SIN .duckdns.org
TOKEN="TU_TOKEN_AQUI"  # Reemplazar con tu token de DuckDNS

# Actualizar IP
echo url="https://www.duckdns.org/update?domains=${DOMAIN}&token=${TOKEN}&ip=" | curl -k -o /var/log/duckdns.log -K -

# Verificar resultado
RESULT=$(cat /var/log/duckdns.log)
DATE=$(date '+%Y-%m-%d %H:%M:%S')

if [ "$RESULT" = "OK" ]; then
    echo "[$DATE]  IP actualizada correctamente" >> /var/log/duckdns-history.log
else
    echo "[$DATE]  Error al actualizar IP: $RESULT" >> /var/log/duckdns-history.log
fi

# Mostrar resultado
echo "[$DATE] Resultado: $RESULT"
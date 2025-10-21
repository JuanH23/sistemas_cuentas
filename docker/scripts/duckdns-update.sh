#!/bin/bash

# ===================================
# CONFIGURACIÓN - CAMBIAR ESTOS VALORES
# ===================================
DOMAIN="misistema"  # SIN .duckdns.org
TOKEN="TU_TOKEN_DE_DUCKDNS_AQUI"

# ===================================
# NO MODIFICAR ABAJO DE ESTA LÍNEA
# ===================================

# Actualizar IP en DuckDNS
echo url="https://www.duckdns.org/update?domains=${DOMAIN}&token=${TOKEN}&ip=" | curl -k -o /var/log/duckdns.log -K -

# Obtener resultado
RESULT=$(cat /var/log/duckdns.log)
DATE=$(date '+%Y-%m-%d %H:%M:%S')

# Log
if [ "$RESULT" = "OK" ]; then
    echo "[$DATE] ✅ IP actualizada correctamente" >> /var/log/duckdns-history.log
else
    echo "[$DATE] ❌ Error al actualizar IP: $RESULT" >> /var/log/duckdns-history.log
fi

# Mostrar resultado
echo "[$DATE] Resultado: $RESULT"
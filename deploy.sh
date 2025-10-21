#!/bin/bash

set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}╔════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║  🚀 Desplegando Sistema de Cuentas        ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════╝${NC}"
echo ""

if [ ! -f .env ]; then
    echo -e "${RED}❌ .env no encontrado${NC}"
    exit 1
fi

export $(cat .env | grep -v '^#' | xargs)

echo -e "${GREEN}🌐 Dominio: $APP_URL${NC}"
echo ""

echo -e "${YELLOW}🛑 Deteniendo contenedores...${NC}"
docker-compose down 2>/dev/null || true

echo -e "${YELLOW}🔨 Construyendo imágenes (esto puede tardar 5-10 min)...${NC}"
docker-compose build --no-cache php

echo -e "${YELLOW}🚢 Levantando servicios...${NC}"
docker-compose up -d

echo -e "${YELLOW}⏳ Esperando MySQL (30s)...${NC}"
sleep 30

until docker-compose exec -T mysql mysqladmin ping -h"localhost" -uroot -p"${DB_ROOT_PASSWORD}" --silent 2>/dev/null; do
    echo -e "${YELLOW}⏳ MySQL no está listo...${NC}"
    sleep 5
done

echo -e "${GREEN}✅ MySQL listo${NC}"

echo -e "${YELLOW}📦 Instalando dependencias Composer...${NC}"
docker-compose exec -T php composer install --no-dev --optimize-autoloader --no-interaction

if ! grep -q "APP_KEY=base64:" .env; then
    echo -e "${YELLOW}🔑 Generando APP_KEY...${NC}"
    docker-compose exec -T php php artisan key:generate --force
fi

echo -e "${YELLOW}🧹 Limpiando cachés...${NC}"
docker-compose exec -T php php artisan config:clear
docker-compose exec -T php php artisan cache:clear
docker-compose exec -T php php artisan view:clear
docker-compose exec -T php php artisan route:clear

echo -e "${YELLOW}⚡ Optimizando...${NC}"
docker-compose exec -T php php artisan config:cache
docker-compose exec -T php php artisan route:cache
docker-compose exec -T php php artisan view:cache

echo -e "${YELLOW}🔗 Storage link...${NC}"
docker-compose exec -T php php artisan storage:link 2>/dev/null || true

echo ""
read -p "¿Ejecutar migraciones BD CENTRAL? (s/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[SsYy]$ ]]; then
    docker-compose exec -T php php artisan migrate --database=central --force
fi

echo -e "${YELLOW}🔐 Ajustando permisos...${NC}"
docker-compose exec -T php chown -R www-data:www-data /var/www/html/storage
docker-compose exec -T php chown -R www-data:www-data /var/www/html/bootstrap/cache
docker-compose exec -T php chmod -R 775 /var/www/html/storage
docker-compose exec -T php chmod -R 775 /var/www/html/bootstrap/cache

echo ""
echo -e "${GREEN}✅ ¡Despliegue completado!${NC}"
echo ""
docker-compose ps
echo ""
echo -e "${BLUE}🌐 Accesos:${NC}"
echo -e "   Central:     ${APP_URL}"
echo -e "   Admin:       ${APP_URL}/admin"
echo ""
# 👋 Bienvenido - Empieza Aquí

Documentación completa del API de Apple Wallet Passes.

## 🚀 Inicia en 30 segundos

Elige tu perfil:

### 👨‍💻 Soy Desarrollador
**Tiempo: 15 minutos**

1. Lee: [README.md](./README.md)
2. Lee: [QUICK_REFERENCE.md](./QUICK_REFERENCE.md)
3. Ve a tu lenguaje en: [INTEGRATION_GUIDE.md](./INTEGRATION_GUIDE.md)

### 📱 Soy Desarrollador Mobile
**Tiempo: 20 minutos**

1. Lee: [README.md](./README.md)
2. Ve a tu plataforma en: [INTEGRATION_GUIDE.md](./INTEGRATION_GUIDE.md)
   - iOS/Swift
   - Android/Kotlin

### 🧪 Soy QA/Tester
**Tiempo: 10 minutos**

1. Abre Postman
2. Importa: [Wallet_Passes_API.postman_collection.json](./Wallet_Passes_API.postman_collection.json)
3. Lee: [QUICK_REFERENCE.md](./QUICK_REFERENCE.md)

### 📊 Soy Manager/PM
**Tiempo: 10 minutos**

1. Lee: [SUMMARY.txt](./SUMMARY.txt)
2. Lee: [SHARING_INSTRUCTIONS.md](./SHARING_INSTRUCTIONS.md)

### 🏗️ Soy Arquitecto
**Tiempo: 1 hora**

1. Lee: [API_WALLET_PASSES.md](./API_WALLET_PASSES.md)
2. Lee: [FAQ_AND_BEST_PRACTICES.md](./FAQ_AND_BEST_PRACTICES.md)
3. Revisa: [FILES_OVERVIEW.md](./FILES_OVERVIEW.md)

---

## 📚 Documentos disponibles

| Documento | Tamaño | Para quién | Tiempo |
|-----------|--------|-----------|--------|
| [README.md](./README.md) | 8 KB | Todos | 5-10 min |
| **[API_WALLET_PASSES.md](./API_WALLET_PASSES.md)** | 20 KB | Backend/Arquitecto | 30-45 min |
| [QUICK_REFERENCE.md](./QUICK_REFERENCE.md) | 8 KB | Dev activo | 5-10 min |
| [INTEGRATION_GUIDE.md](./INTEGRATION_GUIDE.md) | 24 KB | Frontend/Mobile | 20-30 min |
| [FAQ_AND_BEST_PRACTICES.md](./FAQ_AND_BEST_PRACTICES.md) | 16 KB | Dev avanzado | 15-25 min |
| [DOCUMENTATION_INDEX.md](./DOCUMENTATION_INDEX.md) | 12 KB | Nuevos usuarios | 10-15 min |
| [FILES_OVERVIEW.md](./FILES_OVERVIEW.md) | 12 KB | Contexto | 10-15 min |
| [SUMMARY.txt](./SUMMARY.txt) | 16 KB | Ejecutivos | 10 min |
| [SHARING_INSTRUCTIONS.md](./SHARING_INSTRUCTIONS.md) | 8 KB | Managers | 5-10 min |

---

## 🎯 Inicio rápido

### Crear un pass

```bash
curl -X POST http://localhost:8000/api/v1/passes \
  -H "Authorization: ApplePass my-token" \
  -H "Content-Type: application/json" \
  -d '{
    "pass_type_identifier": "pass.com.example",
    "serial_number": "pass-001",
    "template_type": "generic",
    "data": {
      "description": "Mi pass",
      "organizationName": "Mi Empresa"
    }
  }'
```

### Descargar el pass

```bash
curl -X GET http://localhost:8000/api/v1/passes/pass.com.example/pass-001 \
  -H "Authorization: ApplePass my-token" \
  -o pass.pkpass
```

### Registrar dispositivo

```bash
curl -X POST http://localhost:8000/api/v1/devices/device-id/registrations/pass.com.example/pass-001 \
  -H "Authorization: ApplePass my-token" \
  -d '{"pushToken": "aaaa... (64 hex chars)"}'
```

---

## 📁 Estructura de documentación

```
docs/
├── START_HERE.md ← TÚ ESTÁS AQUÍ
├── README.md ⭐ Visión general
├── API_WALLET_PASSES.md ⭐ Referencia técnica
├── QUICK_REFERENCE.md ⭐ Consulta rápida
├── INTEGRATION_GUIDE.md ⭐ Ejemplos de código
├── FAQ_AND_BEST_PRACTICES.md ⭐ Ayuda
├── DOCUMENTATION_INDEX.md - Índice
├── FILES_OVERVIEW.md - Descripción de archivos
├── SUMMARY.txt - Resumen ejecutivo
└── SHARING_INSTRUCTIONS.md - Cómo compartir
```

---

## ✨ Características

✅ 7 endpoints REST documentados  
✅ 13 tests (13/13 pasando)  
✅ 100+ ejemplos de código  
✅ 6 lenguajes soportados  
✅ Colección Postman lista  
✅ 17 FAQs respondidas  
✅ Mejores prácticas documentadas  
✅ Seguridad incluida  

---

## 📱 Plataformas soportadas

- iOS/Swift
- Android/Kotlin
- Web/JavaScript
- PHP/Laravel
- Python
- Postman

---

## 🧪 Testing

```bash
docker exec zeldaid-crmservice.local.test-1 \
  php artisan test tests/Feature/WalletPassApiTest.php
```

Resultado: **13/13 PASANDO ✅**

---

## 🔐 Autenticación

```
Authorization: ApplePass {authToken}
```

---

## ❓ Preguntas comunes

**P: ¿Cuál es el primer documento que debo leer?**  
R: [README.md](./README.md) (5 minutos)

**P: ¿Dónde están los ejemplos de código?**  
R: [INTEGRATION_GUIDE.md](./INTEGRATION_GUIDE.md)

**P: ¿Cómo testeo el API?**  
R: [Wallet_Passes_API.postman_collection.json](./Wallet_Passes_API.postman_collection.json)

**P: Tengo una duda, ¿dónde buscar?**  
R: [FAQ_AND_BEST_PRACTICES.md](./FAQ_AND_BEST_PRACTICES.md)

---

## 📞 Próximos pasos

1. Abre [README.md](./README.md)
2. Elige tu lenguaje en [INTEGRATION_GUIDE.md](./INTEGRATION_GUIDE.md)
3. Importa Postman collection si quieres testear
4. Consulta [FAQ_AND_BEST_PRACTICES.md](./FAQ_AND_BEST_PRACTICES.md) si tienes dudas

---

## 🎉 Estado

- Implementación: ✅ COMPLETA
- Testing: ✅ PASANDO
- Documentación: ✅ COMPLETA
- Listo para: ✅ PRODUCCIÓN

---

**¿Listo? → Abre [README.md](./README.md)**

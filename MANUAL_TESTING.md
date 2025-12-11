# TestLink Manual Testing Guide

Bu dokuman, TestLink'in manuel test edilmesi icin iteratif bir rehber sunar.

## Fase 1: Kurulum Sonrasi (Hic Link Yok)

TestLink'i yukledikten hemen sonra, henuz hicbir `#[TestedBy]` veya `linksAndCovers()` yokken:

### 1.1 CLI Temel Kontroller

```bash
# Help goruntulemeli
vendor/bin/testlink --help
vendor/bin/testlink -h

# Versiyon goruntulemeli
vendor/bin/testlink --version
vendor/bin/testlink -v

# Framework detection kontrolu
# Pest projesi: "pest (phpunit compatible)"
# PHPUnit projesi: "phpunit"
# Hicbiri yoksa: "none"
vendor/bin/testlink

# Bilinmeyen komut hata vermeli
vendor/bin/testlink unknown

# No-color modu calismali
vendor/bin/testlink --no-color
```

### 1.2 Bos Proje Komutlari

```bash
# Report: "No coverage links found" veya bos rapor
vendor/bin/testlink report
vendor/bin/testlink report --json

# Validate: Basarili (link yok = hata yok)
vendor/bin/testlink validate
vendor/bin/testlink validate --json
vendor/bin/testlink validate --strict

# Sync: "Nothing to sync" veya bos
vendor/bin/testlink sync --dry-run

# Pair: "No placeholders found"
vendor/bin/testlink pair --dry-run
```

### 1.3 Command Help

```bash
vendor/bin/testlink report --help
vendor/bin/testlink validate --help
vendor/bin/testlink sync --help
vendor/bin/testlink pair --help
```

---

## Fase 2: Ilk TestedBy Attribute'u

Bir production metoduna `#[TestedBy]` ekle:

```php
// src/Services/UserService.php (veya herhangi bir class)
use TestFlowLabs\TestingAttributes\TestedBy;

class UserService
{
    #[TestedBy(UserServiceTest::class, 'it creates a user')]
    public function create(array $data): User
    {
        // ...
    }
}
```

### 2.1 Report Kontrolleri

```bash
# Link gorunmeli
vendor/bin/testlink report

# JSON formatinda olmali
vendor/bin/testlink report --json

# Verbose daha fazla detay gostermeli
vendor/bin/testlink report --verbose

# Path filtresi calismali
vendor/bin/testlink report --path=src/Services
```

### 2.2 Validate Kontrolleri

```bash
# Link sayisi gorunmeli
vendor/bin/testlink validate

# JSON valid olmali
vendor/bin/testlink validate --json
```

### 2.3 Sync Kontrolleri

```bash
# Test dosyasina eklenecek linki gostermeli
vendor/bin/testlink sync --dry-run

# Gercekten eklemeli (test dosyasini kontrol et)
vendor/bin/testlink sync

# Test dosyasinda linksAndCovers (Pest) veya #[LinksAndCovers] (PHPUnit) olmali
```

---

## Fase 3: Placeholder Kullanimi

Production ve test'e placeholder ekle:

```php
// src/Services/OrderService.php
use TestFlowLabs\TestingAttributes\TestedBy;

class OrderService
{
    #[TestedBy('@order-create')]
    public function create(array $items): Order
    {
        // ...
    }
}
```

```php
// tests/Unit/OrderServiceTest.php (Pest)
test('creates an order', function () {
    // ...
})->linksAndCovers('@order-create');
```

### 3.1 Validate ile Placeholder Tespiti

```bash
# "Unresolved Placeholders" uyarisi gostermeli
vendor/bin/testlink validate

# JSON'da unresolvedPlaceholders olmali
vendor/bin/testlink validate --json

# Strict mode'da FAIL olmali (exit code 1)
vendor/bin/testlink validate --strict
echo $?  # 1 olmali
```

### 3.2 Pair Dry-Run

```bash
# Yapilacak degisiklikleri gostermeli
# "@order-create  1 production x 1 test = 1 link"
vendor/bin/testlink pair --dry-run
```

### 3.3 Pair Uygula

```bash
# Placeholder'lari resolve et
vendor/bin/testlink pair

# Dosyalari kontrol et:
# - Production: #[TestedBy('@order-create')] -> #[TestedBy(OrderServiceTest::class, 'it creates an order')]
# - Test: ->linksAndCovers('@order-create') -> ->linksAndCovers(OrderService::class.'::create')
```

### 3.4 Pair Sonrasi Validate

```bash
# Artik placeholder uyarisi OLMAMALI
vendor/bin/testlink validate

# Strict mode PASS olmali (exit code 0)
vendor/bin/testlink validate --strict
echo $?  # 0 olmali
```

---

## Fase 4: N:M Placeholder Matching

Ayni placeholder'i birden fazla yerde kullan:

```php
// Production: 2 metod
class PaymentService
{
    #[TestedBy('@payment')]
    public function charge(): void { }

    #[TestedBy('@payment')]
    public function refund(): void { }
}
```

```php
// Test: 3 test
test('charges payment', fn() => ...)->linksAndCovers('@payment');
test('validates payment', fn() => ...)->linksAndCovers('@payment');
test('refunds payment', fn() => ...)->linksAndCovers('@payment');
```

### 4.1 N:M Kontrolu

```bash
# "2 production x 3 tests = 6 links" gostermeli
vendor/bin/testlink pair --dry-run

# Uygula ve kontrol et
vendor/bin/testlink pair

# Her production metod 3 TestedBy'a sahip olmali
# Her test 2 linksAndCovers'a sahip olmali
```

---

## Fase 5: Sync Ozellikleri

### 5.1 Link-Only Modu

```bash
# linksAndCovers yerine links kullanmali
vendor/bin/testlink sync --link-only --dry-run
vendor/bin/testlink sync --link-only
```

### 5.2 Prune (Orphan Temizleme)

Oncelikle bir test'e manuel olarak artik production'da olmayan bir link ekle:

```php
test('some test', function () {
    // ...
})->linksAndCovers(DeletedService::class.'::deletedMethod');
```

```bash
# Force olmadan hata vermeli
vendor/bin/testlink sync --prune

# Dry-run ile ne silinecek gostermeli
vendor/bin/testlink sync --prune --force --dry-run

# Uygula
vendor/bin/testlink sync --prune --force
```

### 5.3 Framework Filtresi

```bash
vendor/bin/testlink sync --framework=pest --dry-run
vendor/bin/testlink sync --framework=phpunit --dry-run
```

---

## Fase 6: Hata Durumlari

### 6.1 Orphan Placeholder

Sadece production'da placeholder var, test'te yok:

```php
// Production
#[TestedBy('@orphan-prod')]
public function orphanMethod(): void { }
```

```bash
# Hata gostermeli: "Placeholder @orphan-prod has no matching test entries"
vendor/bin/testlink pair --dry-run
```

### 6.2 Orphan Test Placeholder

Sadece test'te placeholder var, production'da yok:

```php
// Test
test('orphan test', fn() => ...)->linksAndCovers('@orphan-test');
```

```bash
# Hata gostermeli: "Placeholder @orphan-test has no matching production entries"
vendor/bin/testlink pair --dry-run
```

### 6.3 Gecersiz Placeholder Formati

```bash
# Hata vermeli
vendor/bin/testlink pair --placeholder=invalid
vendor/bin/testlink pair --placeholder=@123
vendor/bin/testlink pair --placeholder=@
```

### 6.4 Gecerli Placeholder Formatlari

```bash
# Bunlar calismali (eger varsa)
vendor/bin/testlink pair --placeholder=@A
vendor/bin/testlink pair --placeholder=@user-create
vendor/bin/testlink pair --placeholder=@UserCreate123
vendor/bin/testlink pair --placeholder=@test_helper
```

---

## Fase 7: PHPUnit Spesifik

PHPUnit test dosyasi olustur:

```php
// tests/Unit/PHPUnitExampleTest.php
use PHPUnit\Framework\TestCase;
use TestFlowLabs\TestingAttributes\LinksAndCovers;

class PHPUnitExampleTest extends TestCase
{
    #[LinksAndCovers('@phpunit-test')]
    public function test_example(): void
    {
        $this->assertTrue(true);
    }
}
```

Production'a ekle:

```php
#[TestedBy('@phpunit-test')]
public function exampleMethod(): void { }
```

```bash
# PHPUnit testleri de tespit edilmeli
vendor/bin/testlink validate
vendor/bin/testlink pair --dry-run
vendor/bin/testlink pair

# Sonuc: #[LinksAndCovers(ExampleClass::class, 'exampleMethod')] olmali
```

---

## Fase 8: Edge Cases

### 8.1 Coklu Attribute Ayni Metod

```php
#[TestedBy(TestA::class, 'test_one')]
#[TestedBy(TestB::class, 'test_two')]
#[TestedBy('@placeholder')]
public function multiAttributeMethod(): void { }
```

### 8.2 Chained linksAndCovers

```php
test('chained test', function () {
    // ...
})->linksAndCovers(ServiceA::class.'::methodA')
  ->linksAndCovers(ServiceB::class.'::methodB')
  ->linksAndCovers('@placeholder');
```

### 8.3 Describe Block Icinde

```php
describe('UserService', function () {
    test('creates user', function () {
        // ...
    })->linksAndCovers('@user');

    test('updates user', function () {
        // ...
    })->linksAndCovers('@user');
});
```

### 8.4 it() Syntax

```php
it('does something', function () {
    // ...
})->linksAndCovers(Service::class.'::method');
```

---

## Kontrol Listesi

Her fase sonrasi kontrol edilecekler:

- [ ] Komut hata vermeden calisiyor
- [ ] Cikti beklenen formatta
- [ ] Exit code dogru (0 = basari, 1 = hata)
- [ ] Dosya degisiklikleri dogru
- [ ] JSON output gecerli ve parse edilebilir
- [ ] --dry-run gercekten dosya degistirmiyor
- [ ] --verbose daha fazla bilgi gosteriyor

---

## CLI Cikti Kontrolleri

Her komut icin beklenen cikti formatlarini kontrol et:

### Help Ciktisi

```
vendor/bin/testlink --help
```

Beklenen:
```
TestLink dev-master

  Detected frameworks: pest (phpunit compatible)


  USAGE
    testlink <command> [options]

  COMMANDS
    • report      Show coverage links report
    • validate    Validate coverage link synchronization
    • sync        Sync coverage links across test files
    • pair        Resolve placeholder markers into real links

  GLOBAL OPTIONS
    • --help, -h        Show help information
    • --version, -v     Show version
    • --verbose         Show detailed output
    • --no-color        Disable colored output

  Run "testlink <command> --help" for command-specific help.
```

### Version Ciktisi

```
vendor/bin/testlink --version
```

Beklenen:
```
TestLink dev-master
```

### Report Ciktisi (Link Varken)

```
vendor/bin/testlink report
```

Beklenen:
```
  Coverage Links Report
  ─────────────────────

  App\Services\UserService

    create()
      → Tests\Unit\UserServiceTest::it creates a user

  Summary
    Methods with tests: 1
    Total test links: 1
```

### Report JSON Ciktisi

```
vendor/bin/testlink report --json
```

Beklenen (gecerli JSON):
```json
{
  "links": {
    "App\\Services\\UserService::create": [
      "Tests\\Unit\\UserServiceTest::it creates a user"
    ]
  },
  "summary": {
    "total_methods": 1,
    "total_tests": 1
  }
}
```

### Validate Ciktisi (Basarili)

```
vendor/bin/testlink validate
```

Beklenen:
```
  Validation Report
  ─────────────────

  Link Summary
  ────────────

    PHPUnit attribute links: 0
    Pest method chain links: 1
    Total links: 1

  ✓ All links are valid!
```

### Validate Ciktisi (Placeholder Varken)

```
vendor/bin/testlink validate
```

Beklenen:
```
  Validation Report
  ─────────────────

  Unresolved Placeholders
  ───────────────────────

    ⚠ @order-create  (1 production, 1 tests)

    ⚠ Run "testlink pair" to resolve placeholders.

  Link Summary
  ────────────

    PHPUnit attribute links: 0
    Pest method chain links: 0
    Total links: 0

  ✓ All links are valid!
```

### Validate JSON Ciktisi

```
vendor/bin/testlink validate --json
```

Beklenen:
```json
{
  "valid": true,
  "totalLinks": 1,
  "phpunitLinks": 0,
  "pestLinks": 1,
  "duplicates": [],
  "unresolvedPlaceholders": []
}
```

### Sync Dry-Run Ciktisi

```
vendor/bin/testlink sync --dry-run
```

Beklenen:
```
  Sync Coverage Links
  ───────────────────

  Running in dry-run mode. No files will be modified.

  Changes to apply:
  ─────────────────

    tests/Unit/UserServiceTest.php
      + linksAndCovers(UserService::class.'::create')

  Dry run complete. Would modify 1 file(s).

    Run without --dry-run to apply changes:
    testlink sync
```

### Pair Dry-Run Ciktisi

```
vendor/bin/testlink pair --dry-run
```

Beklenen:
```
  Pairing Placeholders
  ────────────────────

  Running in dry-run mode. No files will be modified.

  Scanning for placeholders...

  Found Placeholders
  ──────────────────

    ✓ @order-create  1 production × 1 tests = 1 links

  Production Files
  ────────────────

    src/Services/OrderService.php
      @order-create → OrderServiceTest::it creates an order

  Test Files
  ──────────

    tests/Unit/OrderServiceTest.php
      @order-create → OrderService::create

  Dry run complete. Would modify 2 file(s) with 2 change(s).

    Run without --dry-run to apply changes:
    testlink pair
```

### Pair Ciktisi (Basarili)

```
vendor/bin/testlink pair
```

Beklenen:
```
  Pairing Placeholders
  ────────────────────

  Scanning for placeholders...

  Found Placeholders
  ──────────────────

    ✓ @order-create  1 production × 1 tests = 1 links

  Production Files
  ────────────────

    src/Services/OrderService.php
      @order-create → OrderServiceTest::it creates an order

  Test Files
  ──────────

    tests/Unit/OrderServiceTest.php
      @order-create → OrderService::create

  ✓ Pairing complete. Modified 2 file(s) with 2 change(s).
```

### Pair Ciktisi (Placeholder Bulunamadi)

```
vendor/bin/testlink pair --dry-run
```

Beklenen:
```
  Pairing Placeholders
  ────────────────────

  Scanning for placeholders...

  No placeholders found.
```

### Pair Ciktisi (Orphan Placeholder)

```
vendor/bin/testlink pair --dry-run
```

Beklenen:
```
  Pairing Placeholders
  ────────────────────

  Scanning for placeholders...

  Found Placeholders
  ──────────────────

    ✗ @orphan  1 production × 0 tests = 0 links

  Errors
  ──────

    ✗ Placeholder @orphan has no matching test entries
```

### Hata Ciktilari

Gecersiz placeholder:
```
vendor/bin/testlink pair --placeholder=invalid
```

Beklenen:
```
  ✗ Invalid placeholder format: invalid
```

Bilinmeyen komut:
```
vendor/bin/testlink unknown
```

Beklenen:
```
  Unknown command: unknown

  Run "testlink --help" for available commands.
```

---

## Hizli Test Scripti

Tum temel komutlari hizlica test etmek icin:

```bash
#!/bin/bash
set -e

echo "=== TestLink Manual Test ==="

echo -e "\n1. Version"
vendor/bin/testlink --version

echo -e "\n2. Help"
vendor/bin/testlink --help

echo -e "\n3. Report"
vendor/bin/testlink report

echo -e "\n4. Report JSON"
vendor/bin/testlink report --json

echo -e "\n5. Validate"
vendor/bin/testlink validate

echo -e "\n6. Validate JSON"
vendor/bin/testlink validate --json

echo -e "\n7. Sync Dry-Run"
vendor/bin/testlink sync --dry-run

echo -e "\n8. Pair Dry-Run"
vendor/bin/testlink pair --dry-run

echo -e "\n=== All basic tests passed ==="
```

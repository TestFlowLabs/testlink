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

### 8.5 Nested Describe Blocks

```php
describe('UserService', function () {
    describe('create method', function () {
        it('creates a user with valid data', function () {
            // ...
        })->linksAndCovers('@nested-describe');

        it('validates email format', function () {
            // ...
        })->linksAndCovers('@nested-describe');
    });

    describe('delete method', function () {
        it('soft deletes the user', function () {
            // ...
        })->linksAndCovers('@nested-describe');
    });
});
```

Production:
```php
#[TestedBy('@nested-describe')]
public function create(): void { }

#[TestedBy('@nested-describe')]
public function delete(): void { }
```

```bash
# Test identifier'lar su formatta olmali:
# - UserService > create method > creates a user with valid data
# - UserService > create method > validates email format
# - UserService > delete method > soft deletes the user
vendor/bin/testlink pair --dry-run
```

### 8.6 Arrow Function Syntax

```php
// Kisa test syntaxi
test('arrow function test', fn() => expect(true)->toBeTrue())
    ->linksAndCovers('@arrow-test');

// Tek satirlik it()
it('works with arrow', fn() => expect(1)->toBe(1))
    ->linksAndCovers('@arrow-test');
```

Production:
```php
#[TestedBy('@arrow-test')]
public function arrowMethod(): void { }
```

```bash
# Arrow function testler de tespit edilmeli
vendor/bin/testlink pair --dry-run
vendor/bin/testlink pair
```

### 8.7 Ayni Method'da Birden Fazla Farkli Placeholder

```php
// Production - tek metod, birden fazla farkli placeholder
class AuthService
{
    #[TestedBy('@auth-login')]
    #[TestedBy('@auth-validation')]
    #[TestedBy('@security-check')]
    public function login(string $email, string $password): User
    {
        // ...
    }
}
```

```php
// Tests - her placeholder farkli test grubu
test('logs in user', fn() => ...)->linksAndCovers('@auth-login');
test('validates credentials', fn() => ...)->linksAndCovers('@auth-validation');
test('checks security rules', fn() => ...)->linksAndCovers('@security-check');
```

```bash
# Her placeholder ayri ayri resolve edilmeli
vendor/bin/testlink pair --dry-run

# Belirli placeholder secimi
vendor/bin/testlink pair --placeholder=@auth-login
vendor/bin/testlink pair --placeholder=@auth-validation

# Tumu
vendor/bin/testlink pair
```

### 8.8 PHPUnit DataProvider ile Test

```php
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use TestFlowLabs\TestingAttributes\LinksAndCovers;

class CalculatorTest extends TestCase
{
    #[LinksAndCovers('@calc-add')]
    #[DataProvider('additionProvider')]
    public function test_addition(int $a, int $b, int $expected): void
    {
        $calc = new Calculator();
        $this->assertEquals($expected, $calc->add($a, $b));
    }

    public static function additionProvider(): array
    {
        return [
            [1, 2, 3],
            [0, 0, 0],
            [-1, 1, 0],
        ];
    }
}
```

Production:
```php
#[TestedBy('@calc-add')]
public function add(int $a, int $b): int { return $a + $b; }
```

```bash
# DataProvider testler de tespit edilmeli
vendor/bin/testlink validate
vendor/bin/testlink pair --dry-run
```

---

## Fase 9: Path ve Framework Filtreleri

### 9.1 --path Secenegi

```bash
# Sadece belirli dizini tara
vendor/bin/testlink report --path=src/Services
vendor/bin/testlink report --path=app/Models

# Test dizini ile
vendor/bin/testlink validate --path=tests/Unit
vendor/bin/testlink validate --path=tests/Feature

# Sync ile
vendor/bin/testlink sync --path=tests/Unit --dry-run
```

### 9.2 --framework Secenegi

```bash
# Sadece Pest testlerini isle
vendor/bin/testlink report --framework=pest
vendor/bin/testlink sync --framework=pest --dry-run

# Sadece PHPUnit testlerini isle
vendor/bin/testlink report --framework=phpunit
vendor/bin/testlink sync --framework=phpunit --dry-run

# Auto (varsayilan)
vendor/bin/testlink sync --framework=auto --dry-run
```

### 9.3 Path ve Framework Birlikte

```bash
# Belirli dizinde, belirli framework
vendor/bin/testlink sync --path=tests/Unit --framework=phpunit --dry-run
vendor/bin/testlink report --path=src/Services --framework=pest
```

---

## Fase 10: Ozel Karakterler ve Uzun Isimler

### 10.1 Ozel Karakterli Test Isimleri

```php
test('it handles "quoted" strings', fn() => ...)
    ->linksAndCovers('@special-chars');

test("it works with 'single quotes'", fn() => ...)
    ->linksAndCovers('@special-chars');

test('handles émojis 🎉 and ünïcödé', fn() => ...)
    ->linksAndCovers('@special-chars');
```

### 10.2 Cok Uzun Test Isimleri

```php
test('this is a very long test name that describes exactly what the test does including all edge cases and expected behaviors when the user performs a specific action', function () {
    // ...
})->linksAndCovers('@long-name');
```

```bash
# Uzun isimler truncate edilmemeli, tam olarak eslesmeli
vendor/bin/testlink pair --dry-run
```

---

## Fase 11: @see Tag Temel Kullanim

@see tag'ler sync komutuyla otomatik olusturulur ve IDE'de tam method navigasyonu saglar.

### 11.1 Sync ile @see Olusturma

Production dosyasina TestedBy ekle:

```php
// src/Services/UserService.php
use TestFlowLabs\TestingAttributes\TestedBy;

class UserService
{
    #[TestedBy(UserServiceTest::class, 'test_creates_user')]
    public function create(): User
    {
        // ...
    }
}
```

```bash
# Sync calistir
vendor/bin/testlink sync

# Sonuc: Production dosyasinda @see tag olusacak
```

Beklenen sonuc:
```php
/**
 * @see \Tests\Unit\UserServiceTest::test_creates_user
 */
#[TestedBy(UserServiceTest::class, 'test_creates_user')]
public function create(): User
```

### 11.2 Report'ta @see Goruntuleme

```bash
vendor/bin/testlink report
```

Beklenen cikti:
```
  Coverage Links Report
  ─────────────────────

  App\Services\UserService

    create()
      → Tests\Unit\UserServiceTest::test_creates_user

  @see Tags
  ─────────

  Production code → Tests:
    App\Services\UserService::create
      → Tests\Unit\UserServiceTest::test_creates_user

  Summary
    Methods with tests: 1
    Total test links: 1
    @see tags: 1
```

### 11.3 Validate'te @see Sayisi

```bash
vendor/bin/testlink validate
```

Beklenen cikti:
```
  Validation Report
  ─────────────────

  Link Summary
    PHPUnit attribute links: 1
    @see tags: 1
    Total links: 1

  ✓ All links are valid!
```

### 11.4 PHPUnit Test → Production @see

PHPUnit test dosyasina @see ekle:

```php
// tests/Unit/UserServiceTest.php
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class UserServiceTest extends TestCase
{
    /**
     * @see \App\Services\UserService::create
     */
    #[Test]
    public function test_creates_user(): void
    {
        // ...
    }
}
```

```bash
# Test → Production @see de tespit edilmeli
vendor/bin/testlink report
```

Beklenen cikti "Test code → Production" bolumunde gorunmeli.

---

## Fase 12: @see Tag Orphan Tespiti

### 12.1 Gecersiz Test Class'ina Isaret Eden @see

Manuel olarak gecersiz @see ekle:

```php
// src/Services/UserService.php
/**
 * @see \Tests\Unit\DeletedTest::test_old_method
 */
public function create(): User
```

```bash
vendor/bin/testlink validate
```

Beklenen cikti:
```
  Validation Report
  ─────────────────

  Orphan @see Tags
  ────────────────

    ⚠ @see \Tests\Unit\DeletedTest::test_old_method
      in src/Services/UserService.php:15

  Link Summary
    @see tags: 1 (1 orphan)

  ✓ All links are valid!
```

### 12.2 Gecersiz Method'a Isaret Eden @see

```php
/**
 * @see \Tests\Unit\UserServiceTest::deleted_test
 */
public function create(): User
```

### 12.3 JSON Ciktisinda Orphan @see

```bash
vendor/bin/testlink validate --json
```

Beklenen:
```json
{
  "valid": true,
  "seeTags": {
    "production": 1,
    "test": 0,
    "total": 1,
    "orphans": 1
  },
  "orphanSeeTags": [
    {
      "reference": "\\Tests\\Unit\\DeletedTest::test_old_method",
      "file": "src/Services/UserService.php",
      "line": 15
    }
  ]
}
```

---

## Fase 13: @see Tag Pruning

### 13.1 Orphan @see Temizleme

Orphan @see iceren dosya:
```php
/**
 * @see \Tests\Unit\DeletedTest::old_test
 * @see \Tests\Unit\UserServiceTest::test_creates_user
 */
#[TestedBy(UserServiceTest::class, 'test_creates_user')]
public function create(): User
```

```bash
# Dry-run ile onizleme
vendor/bin/testlink sync --prune --force --dry-run

# Uygula
vendor/bin/testlink sync --prune --force
```

Beklenen sonuc:
```php
/**
 * @see \Tests\Unit\UserServiceTest::test_creates_user
 */
#[TestedBy(UserServiceTest::class, 'test_creates_user')]
public function create(): User
```

### 13.2 Bos Docblock Temizleme

Sadece orphan @see iceren docblock:
```php
/**
 * @see \Tests\Unit\DeletedTest::old_test
 */
public function create(): User
```

Prune sonrasi:
```php
public function create(): User
```

### 13.3 Diger PHPDoc Tag'leri Koruma

```php
/**
 * Create a new user.
 *
 * @param string $name
 * @see \Tests\Unit\DeletedTest::old_test
 * @see \Tests\Unit\UserServiceTest::test_creates_user
 * @return User
 */
```

Prune sonrasi (diger tag'ler korunmali):
```php
/**
 * Create a new user.
 *
 * @param string $name
 * @see \Tests\Unit\UserServiceTest::test_creates_user
 * @return User
 */
```

---

## Fase 14: @see Edge Cases

### 14.1 Mevcut Docblock'a @see Ekleme

Sync oncesi:
```php
/**
 * Create a new user.
 *
 * @param string $name User's name
 * @return User The created user
 */
#[TestedBy(UserServiceTest::class, 'test_creates_user')]
public function create(string $name): User
```

Sync sonrasi:
```php
/**
 * Create a new user.
 *
 * @param string $name User's name
 * @see \Tests\Unit\UserServiceTest::test_creates_user
 * @return User The created user
 */
#[TestedBy(UserServiceTest::class, 'test_creates_user')]
public function create(string $name): User
```

### 14.2 Birden Fazla @see Tag

```php
#[TestedBy(UserServiceTest::class, 'test_creates_user')]
#[TestedBy(UserServiceTest::class, 'test_validates_email')]
public function create(): User
```

Sync sonrasi:
```php
/**
 * @see \Tests\Unit\UserServiceTest::test_creates_user
 * @see \Tests\Unit\UserServiceTest::test_validates_email
 */
#[TestedBy(UserServiceTest::class, 'test_creates_user')]
#[TestedBy(UserServiceTest::class, 'test_validates_email')]
public function create(): User
```

### 14.3 Farkli Indentation Seviyeleri

4 space indent (varsayilan):
```php
class Foo {
    /**
     * @see \Tests\FooTest::test
     */
    public function bar() {}
}
```

2 space indent:
```php
class Foo {
  /**
   * @see \Tests\FooTest::test
   */
  public function bar() {}
}
```

Tab indent:
```php
class Foo {
	/**
	 * @see \Tests\FooTest::test
	 */
	public function bar() {}
}
```

### 14.4 Abstract/Static/Final Metodlar

```php
abstract class BaseService
{
    #[TestedBy(BaseServiceTest::class, 'test_abstract')]
    abstract public function process(): void;

    #[TestedBy(BaseServiceTest::class, 'test_static')]
    public static function getInstance(): self {}

    #[TestedBy(BaseServiceTest::class, 'test_final')]
    final public function lock(): void {}
}
```

Tum method turleri icin @see tag olusturulmali.

### 14.5 Constructor ve Magic Metodlar

```php
class Service
{
    #[TestedBy(ServiceTest::class, 'test_constructor')]
    public function __construct() {}

    #[TestedBy(ServiceTest::class, 'test_invoke')]
    public function __invoke(): void {}

    #[TestedBy(ServiceTest::class, 'test_to_string')]
    public function __toString(): string {}
}
```

### 14.6 Interface ve Trait

```php
interface ServiceInterface
{
    #[TestedBy(ServiceTest::class, 'test_process')]
    public function process(): void;
}

trait Loggable
{
    #[TestedBy(LoggableTest::class, 'test_log')]
    public function log(string $message): void {}
}
```

### 14.7 Duplicate @see Onleme

```php
/**
 * @see \Tests\Unit\UserServiceTest::test_creates_user
 */
#[TestedBy(UserServiceTest::class, 'test_creates_user')]
public function create(): User
```

Sync tekrar calistirildiginda duplicate @see eklenmemeli.

```bash
# Birden fazla kez calistir
vendor/bin/testlink sync
vendor/bin/testlink sync
vendor/bin/testlink sync

# Hala tek @see olmali
```

### 14.8 Pest @see Limitation

Pest icin @see sadece Test → Production yonunde calisir:

```php
/**
 * @see \App\Services\UserService::create
 */
test('creates a user', function () {
    // ...
})->linksAndCovers(UserService::class.'::create');
```

Production → Pest @see CALISMAZ cunku Pest test isimleri space icerir:
```php
// BU CALISMAZ - gecersiz PHP identifier
@see \Tests\Unit\UserServiceTest::creates a user
```

### 14.9 Readonly ve Enum Class'lar

```php
readonly class ImmutableValue
{
    #[TestedBy(ValueTest::class, 'test_create')]
    public function create(): self {}
}

enum Status: string
{
    case ACTIVE = 'active';

    #[TestedBy(StatusTest::class, 'test_label')]
    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Active',
        };
    }
}
```

### 14.10 Anonymous Class (Desteklenmez)

```php
// Anonymous class'lar DESTEKLENMEZ
$service = new class {
    #[TestedBy(Test::class, 'test')]
    public function method() {}
};
```

### 14.11 Nested Class (Desteklenmez)

PHP nested class desteklemedigi icin bu senaryo gecerli degil.

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

### @see Tag Spesifik Kontroller

- [ ] Sync sonrasi @see tag olusturuldu
- [ ] @see tag dogru formatta: `@see \FQCN::method`
- [ ] @see tag dogru indentation ile olusturuldu
- [ ] Mevcut docblock korundu, @see eklendi
- [ ] Duplicate @see eklenmedi
- [ ] Report'ta @see tag'ler gorunuyor
- [ ] Validate'te @see sayisi dogru
- [ ] Orphan @see tespit ediliyor
- [ ] Prune ile orphan @see siliniyor
- [ ] Bos docblock temizleniyor
- [ ] Diger PHPDoc tag'ler korunuyor

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

### Validate Ciktisi (@see Tag'ler ile)

```
vendor/bin/testlink validate
```

Beklenen:
```
  Validation Report
  ─────────────────

  Link Summary
  ────────────

    PHPUnit attribute links: 1
    Pest method chain links: 0
    @see tags: 2
    Total links: 1

  ✓ All links are valid!
```

### Validate Ciktisi (Orphan @see Tag Varken)

```
vendor/bin/testlink validate
```

Beklenen:
```
  Validation Report
  ─────────────────

  Orphan @see Tags
  ────────────────

    ⚠ @see \Tests\Unit\DeletedTest::old_test
      in src/Services/UserService.php:15

  Link Summary
  ────────────

    PHPUnit attribute links: 1
    @see tags: 2 (1 orphan)
    Total links: 1

  ✓ All links are valid!
```

### Report Ciktisi (@see Tag'ler ile)

```
vendor/bin/testlink report
```

Beklenen:
```
  Coverage Links Report
  ─────────────────────

  App\Services\UserService

    create()
      → Tests\Unit\UserServiceTest::test_creates_user

  @see Tags
  ─────────

  Production code → Tests:
    App\Services\UserService::create
      → Tests\Unit\UserServiceTest::test_creates_user

  Test code → Production:
    Tests\Unit\UserServiceTest::test_creates_user
      → App\Services\UserService::create

  Summary
    Methods with tests: 1
    Total test links: 1
    @see tags: 2
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

### @see Tag Test Scripti

@see tag islevselligini test etmek icin:

```bash
#!/bin/bash
set -e

echo "=== @see Tag Tests ==="

# Oncelikle TestedBy attribute'lu bir production method olusturun
# ve sync ile @see tag olusturun

echo -e "\n1. Sync (generates @see tags)"
vendor/bin/testlink sync

echo -e "\n2. Report (@see tags should appear)"
vendor/bin/testlink report

echo -e "\n3. Validate (@see count should appear)"
vendor/bin/testlink validate

echo -e "\n4. Check for orphan @see (add invalid @see manually first)"
# Manuel olarak gecersiz @see ekleyip validate calistirin
vendor/bin/testlink validate

echo -e "\n5. Prune orphan @see tags"
vendor/bin/testlink sync --prune --force --dry-run

echo -e "\n=== @see Tag tests completed ==="
```

### @see Edge Case Kontrol Listesi

Manuel kontrol icin:

```bash
# 1. Mevcut docblock'a @see ekleme
# - @param, @return gibi tag'ler korunmali

# 2. Birden fazla TestedBy = birden fazla @see
vendor/bin/testlink sync
# Dosyayi kontrol et: Her TestedBy icin bir @see olmali

# 3. Duplicate onleme
vendor/bin/testlink sync
vendor/bin/testlink sync
# Dosyayi kontrol et: @see duplicate olmamali

# 4. Indentation
# - 4 space, 2 space, tab icin ayri test

# 5. Abstract/static/final metodlar
# - Tum method turleri icin @see olusturulmali

# 6. Interface ve trait
# - Interface method'lari icin @see olusturulmali
# - Trait method'lari icin @see olusturulmali
```

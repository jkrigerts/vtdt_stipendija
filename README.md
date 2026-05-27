# Stipendiju kalkulators

## Prasības
- Windows 10/11 vai macOS
- Git

## Ieteicamā vide
Windows vidē projekts ir paredzēts darbam ar Laragon, lai visiem lietotājiem būtu vienāda vide: PHP, MySQL, paplašinājumi un servisi.

macOS vidē ieteicams izmantot Homebrew, lai uzstādītu PHP, Composer un MySQL.

## Uzstādīšana (Laragon)

1. Laragon lietotnē nospied `Start All`.
2. Laragon lietotnē atver `Terminal`.
3. Klonē projektu no GitHub:
   `git clone https://github.com/mikstomijs/scholarship.git`
4. Atver projekta mapi:
   `cd scholarship`
5. Nokopē `.env` failu:
   `copy .env.example .env`
6. Instalē PHP atkarības:
   `composer install`
7. Ģenerē lietotnes atslēgu:
   `php artisan key:generate`
8. `.env` failā iestati datubāzes parametrus:
   - `DB_CONNECTION=mysql`
   - `DB_HOST=127.0.0.1`
   - `DB_PORT=3306`
   - `DB_DATABASE=stipendijas`
   - `DB_USERNAME=root`
   - `DB_PASSWORD=`
9. Izpildi migrācijas:
   `php artisan migrate`
10. Ja Laravel piedāvā izveidot datubāzi, apstiprini terminālī ar `yes`.
11. Palaid lietotni:
   `php artisan serve`

Lai piekļūtu lietotnei, interneta pārlūkā atver `http://localhost:8000`.

## Uzstādīšana (macOS)

1. Uzstādi `Homebrew`, ja tas vēl nav pieejams.
2. Uzstādi nepieciešamos rīkus:
   `brew install php composer mysql`
3. Palaid MySQL servisu:
   `brew services start mysql`
4. Klonē projektu no GitHub:
   `git clone https://github.com/mikstomijs/scholarship.git`
5. Atver projekta mapi:
   `cd scholarship`
6. Nokopē `.env` failu:
   `cp .env.example .env`
7. Instalē PHP atkarības:
   `composer install`
8. Ģenerē lietotnes atslēgu:
   `php artisan key:generate`
9. `.env` failā iestati datubāzes parametrus:
   - `DB_CONNECTION=mysql`
   - `DB_HOST=127.0.0.1`
   - `DB_PORT=3306`
   - `DB_DATABASE=stipendijas`
   - `DB_USERNAME=root`
   - `DB_PASSWORD=`
10. Izpildi migrācijas:
    `php artisan migrate`
11. Ja Laravel piedāvā izveidot datubāzi, apstiprini terminālī ar `yes`.
12. Palaid lietotni:
    `php artisan serve`

Lai piekļūtu lietotnei, interneta pārlūkā atver `http://localhost:8000`.

## Piezīmes
- Ja parādās kļūdas par trūkstošiem PHP paplašinājumiem, pārliecinies, ka tiek izmantota pareizā PHP instalācija.


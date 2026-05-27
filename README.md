# Stipendiju kalkulators

## Prasības
- Windows 10/11
- [Laragon](https://laragon.org/)
- Git

## Ieteicamā vide
Šis projekts ir paredzēts darbam ar Laragon, lai visiem lietotājiem būtu vienāda vide: PHP, MySQL, paplašinājumi un servisi.

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

Lai piekļūtu lietotnei, interneta pārlūkā atver http://localhost:8000

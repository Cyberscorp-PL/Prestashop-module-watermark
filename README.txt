Watermark 2.1.0 – lista zmian od wersji 2.0.0 (Cyberscorp)
=====================================================

Moduł bazowy: PrestaShop "watermark" 2.0.0. Poniżej zmiany wprowadzone w kolejnych wersjach.
Wymagania: PrestaShop 1.7.6+ (testowane pod kątem 8.2, przegląd statyczny pod 9), PHP 7.2.5+ do 8.5, rozszerzenie GD.


Wersja 2.1.0 (2026-10-15)
-------------------------
- Nowość: obsługa systemu tłumaczeń PrestaShop (pliki XLF w translations/<język>/ModulesWatermarkAdmin.<język>.xlf,
  domena Modules.Watermark.Admin) dla języków: angielski (EN), polski (PL), niemiecki (DE), włoski (IT),
  francuski (FR) i hiszpański (ES). Teksty ogólne (Włączone/Wyłączone, Zapisz, Lewo/Prawo...) tłumaczy rdzeń PrestaShop.
  Tłumaczenia można edytować w: Międzynarodowe > Tłumaczenia.
- Nowość: ikona modułu (logo.png i logo.gif).
- Zmiana: autor modułu - Cyberscorp (informacja o oryginalnym autorze PrestaShop SA i licencji pozostaje w nagłówkach plików).
- Zmiana: komunikaty "Allowed image formats..." przeniesione do domeny tłumaczeń modułu (wcześniej Admin.Global).

Wersja 2.0.3 (2026-10-15)
-------------------------
- Nowość: jako znak wodny można wgrać plik .png lub .webp (oprócz .jpg i .gif); przezroczystość WebP działa jak w PNG.
- Nowość: znakiem wodnym oznaczane są także oryginalne zdjęcia produktów w formacie JPG, JPEG, PNG i WebP
  (moduł sam wykrywa format oryginału; plik "<id>-watermark.<format>" zachowuje format źródła).
- Nowość: miniatury (z i bez znaku wodnego, "-<hash>") generowane są we wszystkich formatach włączonych w
  Wygląd > Ustawienia obrazów (zawsze JPG, opcjonalnie PNG i WebP – tylko jeśli GD w PHP potrafi je zapisać).
- Zmiana: reguła w .htaccess blokująca bezpośredni dostęp do oryginałów obejmuje teraz .jpg, .png i .webp
  (odświeżana automatycznie przy aktualizacji – upgrade/upgrade-2.0.3.php).
- Zmiana: komunikaty o dozwolonych formatach pliku znaku wodnego (.gif, .jpg, .png, .webp);
  dotychczasowe tłumaczenia tych zdań mogą wymagać ponownego wprowadzenia.
- Poprawka: po wgraniu nowego znaku wodnego odświeżana jest pamięć podręczna rozszerzenia pliku.

Wersja 2.0.2 (2026-10-15)
-------------------------
- Poprawka: array_intersect() na tablicach typów obrazów powodował ostrzeżenie "Array to string conversion"
  (parametr hooka "image_type" jest teraz dopasowywany po nazwie lub ID typu obrazu i nie zmienia stanu modułu).
- Poprawka: usunięto Tools::file_exists_cache() (brak gwarancji istnienia w PrestaShop 9) – zastąpione is_file().
- Przegląd statyczny pod PHP 8.5 (brak użycia wycofanych konstrukcji: imagedestroy, rzutowania (integer)/(double),
  backticki; właściwości klasy zadeklarowane – bez ostrzeżeń o właściwościach dynamicznych).

Wersja 2.0.1 (2026-10-14)
-------------------------
- Poprawka: Symfony ClassNotFoundError "CategoryFetcher" (namespace PrestaShop\Module\Watermark\Addons)
  w konfiguracji modułu – brakowało katalogu vendor/ z Composera. Usunięto baner sugestii Addons
  (renderAddonsSuggestion) oraz powiązane pliki i zależności; composer.json bez zależności, usunięto composer.lock.
- Poprawka: PHP 8.1+ – przestarzałe niejawne rzutowanie float -> int przy nakładaniu znaku wodnego
  (wymiary, pozycje i przezroczystość są liczbami całkowitymi).
- Poprawka: zabezpieczenia przed brakującym plikiem zdjęcia / znaku wodnego (metoda zwraca false zamiast błędu).
- Poprawka: walidacja "wybierz co najmniej jeden typ obrazu".
- Dodano pusty, zabezpieczony katalog vendor/ (.htaccess + index.php), aby PrestaShop nie zgłaszał
  w logach komunikatów o braku/ochronie katalogu vendor.

Wersja 2.0.0
------------
- Wersja wyjściowa (bez zmian).

# Homecredit Myloan Connector Prestashop 1.6

Inicializace:  
- docker-compose up -d
    - defaultní url - dají se přenastavit v docker-compose.yml  
        - http://localhost:8080/ - prestashop 1.6
    - .evn - soubor kde je defaultní nastavení prestashopu a databáze
    - defaultní přístup pro 1.6 - demo@prestashop.com/prestashop_demo
- aktualizace balíčků se provádí přes - composer install - viz Tipy - pokud se provede je potřeba zkontrolovat že je všude index.php
- configPHP/php.ini - zde je upravené nastavení php.ini souboru překopíruje se do dockeru

Testy:
- navigovat se do tests\pupeteer
- testy ve složce test/off se neprovádí 
- zavolat npm test

Tipy:  
- pokud nemáte composer - jde to zavolat přímo přes docker  
    - curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    - navigovat se do složky s modulem (/var/www/html/modules/myloanconnector)
    - php /usr/local/bin/composer install  

Validace modulu:
- https://validator.prestashop.com
- vyhodit testy
- výsledný zip se musí jmenovat myloanconnector.zip
- vložit a nechat z validovat
- vyskočí jedna chyba z hash_hmac - tato chyba je v pořádku dle tohoto komentáře: https://www.prestashop.com/forums/topic/524301-validator-has-problem-with-hash_hmac-function/


#Ovládání pluginu

- moduly a služby
- najít homecredit
- nainstalovat
- nastavit (username, password - dostane při podepsání smlouvy od HC)
- plugin se zobrazuje jen u CZK a EUR - jiné měny nepodporuje
- pokud je cena nad 1000 CZK nebo nad 50 EUR tak se zobrazí možnost kalkulačky
- pokud je objednávka nad 1000 CZK nebo nad 50 EUR tak se zobrazí možnost nákupu na home credit
- následně postupovat podle postupu dle MyLoan
- pokud je úvěr zamítnut, je zákazník přesměrován zpět o eshopu s možností vybrat jinou platební metodu
- pokud je úvěr ověřován, je zákazníkovi zobrazena informace ať vyčká na další instrukce

# Co vše odevzdat
- classes/**
- controllers/**
- dist/**
- translations/**
- vendor/**
- views/**
- ./index.php
- ./myloanconnector.php
 
# Raccolta liberatorie

Webapp in PHP 8 + MySQL/MariaDB (nessuna dipendenza, niente Composer) per raccogliere le liberatorie
per l'uso di foto, video e audio. Richiede le estensioni PHP `pdo_mysql`, `gd`, `mbstring`, `iconv`.

## Funzioni
- Modulo compilabile **solo da operatori autenticati** (stessi utenti dell'area admin): l'intervistato compila e firma sul dispositivo dell'operatore, che resta registrato su ogni liberatoria. Le sessioni scadono dopo 8 ore di inattivita'.
- Modulo: dati anagrafici, consensi (immagini, audio, diffusione, privacy), dichiarazione di maggiore eta', **firma su schermo**.
- Alla firma: PDF generato (testo accettato + dati + firma + data/ora/IP + impronta SHA-256 del testo), salvato in `storage/`, inviato via email all'intervistato e alla redazione, scaricabile dalla pagina di ringraziamento.
- Area admin (`/admin/`): elenco con ricerca/filtri/paginazione, dettaglio, download PDF, export CSV, gestione utenti admin (crea, reimposta password, elimina), cambio password.
- Modelli di liberatoria multipli (`/?t=slug`) con **versioning**: ogni modifica crea una nuova versione; le liberatorie firmate conservano il testo esatto accettato.
- Sicurezza: query preparate, CSRF, honeypot anti-bot, rate limit login (5 tentativi/15 min), cookie di sessione httpOnly/secure/SameSite, header CSP, `storage/` e `lib/` negati via `.htaccess`.

## Installazione
1. Carica i file sul server (Apache + PHP 8.1+), crea un database MySQL vuoto.
2. `cp config.sample.php config.php` e compila DB, email, nome dell'ente.
3. Apri `https://.../install.php`, crea l'utente admin, poi **cancella `install.php`**.
4. Admin > Modelli: il testo predefinito (durata 10 anni, titolare: Scuola di Nuove Tecnologie dell'Arte - ABA Napoli) si puo' modificare da qui; ogni modifica crea una nuova versione. Consigliata revisione legale/DPO.
5. Attiva HTTPS e decommenta il redirect in `.htaccess`. Serve `AllowOverride All` su Apache.

## Aggiornamento da versioni precedenti
Dopo aver caricato i nuovi file, apri una volta `admin/migra.php` (da loggato) per aggiungere la colonna `operatore`, poi cancellalo.

## Note
- Link al modulo: `https://.../` (modello attivo) oppure `https://.../?t=accademia`.
- L'email usa `mail()`: sul server del lab serve un MTA (postfix/msmtp) configurato; altrimenti la liberatoria e' comunque salvata (colonna "Mail" = no).
- Possono firmare solo i maggiorenni; minori/tutori non sono gestiti.
- Accesso da internet: backup regolari di DB e `storage/`, HTTPS obbligatorio, dati personali soggetti a GDPR.

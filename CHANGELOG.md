# Changelog â€“ Emailing Calculator

VĹˇechny vĂ˝raznĂ© zmÄ›ny jsou dokumentovĂˇny v tomto souboru.
FormĂˇt dle [Keep a Changelog](https://keepachangelog.com/cs/1.0.0/).

## [1.7.0] - 2026-10-26

### Změněno
- Výsledek kalkulačky už návštěvníkovi neukazuje balíčky ani ceny. Ukazuje potenciál, odhad obratu, budget při PNO a verdikt „Vyplatí se emailing?“ (vyplatí se / hraničně / spíše ne).
- Jediná konverze výsledku je rezervace konzultace: tlačítko pod kartou verdiktu a blok s výzvou na konci výsledku. GTM `ecalc_cta_click` nese `ecalc_cta_position` (`u-vypoctu` / `dole`).
- REST `/calculate` neposílá `packages` ani `recommended_package`, do GTM se balíček neposílá.
- Z e-mailů klientovi se řádky s balíčkem vynechávají automaticky. Admin e-mail, lead a SmartEmailing balíček dál mají.
- Argumenty „Proč máte tento potenciál“ se ukazují i u nízkého potenciálu.

### Přidáno
- Obchodní režim (zámeček v rohu kalkulačky): po zadání kódu výpočet bez kontaktů, nic se neukládá ani neodesílá, výsledek ukazuje balíčky s cenou a reálným PNO. Kód v Admin → Zabezpečení (výchozí `0101`, změňte ho), ověřuje server, 5 pokusů / 15 min.
- Nové texty v Admin → Texty výsledků (verdikty, výzva ke konzultaci).

### Opraveno
- Název doporučeného balíčku se k leadu neukládal (ukládala se jen cena).

### Odstraněno
- Tlačítka „Poptat balíček“ a děkovací okno poptávky.

## [1.6.0] – 2026-07-13

### Přidáno
- Pole **Telefon** v hlavním formuláři kalkulačky (mezi E-mailem a souhlasy) – povinné, validace 7–15 číslic na frontendu i backendu.

### Změněno
- Telefon se nyní ukládá k leadu hned při prvním odeslání formuláře (dřív jen volitelně přes dialog po kliknutí na CTA).
- Odstraněn dialog „Zanechte nám telefonní číslo" zobrazovaný po kliknutí na CTA „Poptat balíček" – telefon už je v tu chvíli vždy znám z formuláře.

### Odstraněno
- Nastavení textů dialogu pro telefon (`phone_dialog_*`) v admin → Formulář & CTA – dialog byl odstraněn.

## [1.5.9] – 2026-07-06

### Změněno
- Formulář: pole URL e-shopu, Jméno a E-mail přesunuta až za byznysová data (obor, spotřební %, databáze, obrat, PNO), těsně před souhlasy – uživatel nejdřív vidí hodnotu (vyplňuje čísla o svém e-shopu), kontakt zadává až na konec. Přidána přechodová věta „Skvělé, teď už jen kontakt pro zaslání výsledků:".
- Analytika kroků formuláře (abandonment tracking, progress bar) přeuspořádána, aby odpovídala novému pořadí polí.

## [1.5.8] – 2026-07-06

### Přidáno
- Konverzní/UX/copy vylepšení formuláře i výsledků (copywriter+SEO+UX review):
  - Progress indikátor vyplnění formuláře (navazuje na existující step-tracking)
  - Přejmenován label PNO na srozumitelnější formulaci + příklady u slideru spotřebního zboží
  - Mikrocopy pod tlačítkem „Vypočítat" (trust signály), konfigurovatelné
  - Sociální důkaz (Trustindex nebo jiný shortcode) v info panelu i na obrazovce výsledků – nové nastavení v admin → Formulář & CTA
  - Doplňkový text u CTA konzultace (délka/náplň schůzky)
  - Konkrétní slib odezvy po poptání balíčku (do 24 hodin) místo vágní formulace, nyní editovatelné v adminu
  - Neutrální formulace u balíčku s vyšším reálným PNO než zadané (dřív alarmující „Překračuje")
  - Druhé CTA tlačítko přímo v sekci „Proč máte tento potenciál"
  - Benefit-driven věta doplněna do popisů obou balíčků

## [1.5.7] – 2026-07-06

### Přidáno
- U pozitivního a hraničního výsledku se nově zobrazuje sekce „Proč máte tento potenciál" – 3 argumentační body (opakovaný nákup, databáze kontaktů, obor podnikání) + shrnující věta s odhadovaným obratem
- Nová admin stránka **Argumenty** (Nastavení obsahu → Argumenty) – texty pro nízké/střední/vysoké pásmo každého faktoru, konfigurovatelné prahy pásem, možnost sekci vypnout

## [1.5.6] – 2026-06-25

### Přidáno
- SmartEmailing: pole „Doporučený balíček" nyní dostává hodnotu i pro výsledek **Nízký potenciál** (výchozí `Nízký potenciál`, konfigurovatelné v admin → SmartEmailing → Mapování custom polí)
- Nové nastavení „Nízký potenciál bez souhlasu" – umožňuje odesílat leady s nízkým potenciálem do SE i bez marketingového souhlasu

## [1.5.3] – 2026-06-08

### Opraveno
- Honeypot anti-spam: nahrazen inline styl `left:-9999px` CSS třídou `.ecalc-honeypot` (clip-path pattern) – Google Safe Browsing neoznačuje jako podezřelé skryté pole
- Souhlas GDPR: přidán klikatelný odkaz na stránku ochrany osobních údajů v souhlasovém checkboxu
- REST `/check-email`: endpoint přestal vracet interní stav leadu (`status`, `status_label`) neautentizovaným požadavkům – zamezení enumerace databáze

## [1.5.1] – 2026-06-08

### Opraveno
- Odstraněna IP-based spam ochrana – na live webu za Cloudflare sdílela čítač všichni návštěvníci a blokovala SE import

## [1.5.0] â€“ 2026-06-08

### PĹ™idĂˇno
- SmartEmailing prĹŻvodce â€“ detailnĂ­ nĂˇpovÄ›da pro nastavenĂ­ custom polĂ­, tagĹŻ a mapovĂˇnĂ­
- Pole â€žNĂˇzev v SmartEmailingu" (`se_value`) na kaĹľdĂ©m balĂ­ÄŤku â€“ vlastnĂ­ hodnota odeslanĂˇ do SE mĂ­sto internĂ­ho nĂˇzvu
- HromadnĂ˝ export historickĂ˝ch leadĹŻ do SmartEmailingu â€“ s vĂ˝bÄ›rem rozsahu dat
- Ochrana pĹ™ed spamem â€“ pĹ™i 4+ rĹŻznĂ˝ch e-mailech z jednĂ© IP za hodinu se SE import pĹ™eskoÄŤĂ­

### Opraveno
- ManuĂˇlnĂ­ zmÄ›na stavu leadu v adminu se nynĂ­ sprĂˇvnÄ› propisuje do SmartEmailingu vÄŤetnÄ› aktualizace DB statusu
- TypeError: `recommended_package` mĹŻĹľe bĂ˝t array nebo string â€“ normalizovĂˇno pĹ™i odesĂ­lĂˇnĂ­ do SE
- Hodnota odeslanĂˇ do SE custom pole â€žBalĂ­ÄŤek" je nynĂ­ nĂˇzev balĂ­ÄŤku (nebo vlastnĂ­ `se_value`), ne internĂ­ ID

## [1.4.0] â€“ dĹ™Ă­ve

### PĹ™idĂˇno
- KompletnĂ­ ROI kalkulaÄŤka s vĂ˝poÄŤetnĂ­m enginem
- SprĂˇva leadĹŻ, e-mailovĂ© notifikace, SmartEmailing sync
- Analytika, Cloudflare Turnstile, GTM eventy


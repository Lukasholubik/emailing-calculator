# Changelog â€“ Emailing Calculator

VĹˇechny vĂ˝raznĂ© zmÄ›ny jsou dokumentovĂˇny v tomto souboru.
FormĂˇt dle [Keep a Changelog](https://keepachangelog.com/cs/1.0.0/).

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


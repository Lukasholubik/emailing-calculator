# Changelog – Emailing Calculator

Všechny výrazné změny jsou dokumentovány v tomto souboru.
Formát dle [Keep a Changelog](https://keepachangelog.com/cs/1.0.0/).

## [1.5.0] – 2026-06-08

### Přidáno
- SmartEmailing průvodce – detailní nápověda pro nastavení custom polí, tagů a mapování
- Pole „Název v SmartEmailingu" (`se_value`) na každém balíčku – vlastní hodnota odeslaná do SE místo interního názvu
- Hromadný export historických leadů do SmartEmailingu – s výběrem rozsahu dat
- Ochrana před spamem – při 4+ různých e-mailech z jedné IP za hodinu se SE import přeskočí

### Opraveno
- Manuální změna stavu leadu v adminu se nyní správně propisuje do SmartEmailingu včetně aktualizace DB statusu
- TypeError: `recommended_package` může být array nebo string – normalizováno při odesílání do SE
- Hodnota odeslaná do SE custom pole „Balíček" je nyní název balíčku (nebo vlastní `se_value`), ne interní ID

## [1.4.0] – dříve

### Přidáno
- Kompletní ROI kalkulačka s výpočetním enginem
- Správa leadů, e-mailové notifikace, SmartEmailing sync
- Analytika, Cloudflare Turnstile, GTM eventy

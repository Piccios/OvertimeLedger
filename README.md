# Gestore Ore Straordinarie

Un'applicazione web moderna per la gestione delle ore straordinarie con interfaccia multilingua e design cyberpunk.

## 🚀 Caratteristiche

- **Interfaccia Multilingua**: Supporto per Italiano e Inglese
- **Design Cyberpunk**: Stile futuristico con colori neon
- **Gestione Aziende**: Aggiunta, modifica e eliminazione di aziende
- **Tracciamento Ore**: Registrazione dettagliata delle ore straordinarie
- **Riepilogo Mensile**: Visualizzazione delle statistiche mensili
- **Responsive Design**: Ottimizzato per tutti i dispositivi
- **Logo e Favicon**: Branding personalizzato con logo SVG

## 🎨 Design

- **Tema**: Cyberpunk con colori neon (verde, blu, viola)
- **Logo**: SVG personalizzato con effetti neon
- **Favicon**: Icona SVG/PNG per il browser
- **Animazioni**: Transizioni fluide e effetti hover
- **Navigazione**: Bottoni contestuali in base alla pagina corrente

## 📁 Struttura Progetto

```
straordinari/
├── index.php              # File principale dell'applicazione
├── config.php             # Configurazione database
├── database.sql           # Schema database
├── style/
│   └── styles.css        # Stili CSS personalizzati
├── images/
│   ├── logo.svg          # Logo dell'applicazione
│   └── favicon.svg       # Favicon SVG
└── README.md             # Documentazione
```

## 🛠️ Installazione

1. **Clona il repository**:
   ```bash
   git clone [repository-url]
   cd straordinari
   ```

2. **Configura il database**:
   - Importa `database.sql` nel tuo database MySQL
   - Modifica `config.php` con le credenziali del database

3. **Avvia il server**:
   ```bash
   php -S localhost:8000
   ```

4. **Apri nel browser**:
   ```
   http://localhost:8000
   ```

## 🎯 Funzionalità Principali

### Dashboard Principale
- Visualizzazione ore straordinarie della settimana corrente
- Form per aggiungere nuove ore straordinarie
- Statistiche rapide

### Gestione Aziende
- Aggiunta nuove aziende
- Modifica informazioni aziende esistenti
- Eliminazione aziende (con conferma)

### Riepilogo Mensile
- Statistiche dettagliate per mese
- Grafici e tabelle riassuntive
- Filtri per periodo

## 🌐 Multilingua

L'applicazione supporta:
- **Italiano** (default)
- **Inglese**

Il cambio lingua è disponibile tramite il bottone nella navbar.

## 🎨 Personalizzazione

### Colori
I colori principali sono definiti come variabili CSS:
- `--neon-green`: #39ff14
- `--neon-blue`: #00d4ff
- `--neon-purple`: #bc13fe

### Logo
Il logo è un file SVG personalizzato con effetti neon che si adatta al tema cyberpunk.

## 📱 Responsive

L'applicazione è completamente responsive e ottimizzata per:
- Desktop (1200px+)
- Tablet (768px - 1199px)
- Mobile (< 768px)

## 🔧 Tecnologie Utilizzate

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **Framework**: Bootstrap 5.3.0
- **Icone**: Font Awesome 6.0.0
- **Design**: CSS personalizzato con tema cyberpunk

## 📄 Licenza

Questo progetto è rilasciato sotto licenza MIT.

## 🤝 Contributi

I contributi sono benvenuti! Per contribuire:

1. Fai un fork del progetto
2. Crea un branch per la tua feature
3. Committa le modifiche
4. Pusha al branch
5. Apri una Pull Request

## 📞 Supporto

Per supporto o domande, apri una issue su GitHub. 
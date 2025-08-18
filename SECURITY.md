# Sicurezza - Gestore Ore Straordinarie

## Misure di Sicurezza Implementate

### 1. Protezione SQL Injection
- **Prepared Statements**: Tutte le query SQL utilizzano prepared statements con PDO
- **Parametri tipizzati**: Utilizzo di `filter_var()` per validare i tipi di input
- **Escape automatico**: PDO gestisce automaticamente l'escape dei parametri

### 2. Protezione CSRF (Cross-Site Request Forgery)
- **Token CSRF**: Ogni form include un token CSRF univoco
- **Validazione token**: Verifica del token su ogni richiesta POST
- **Regenerazione automatica**: I token vengono rigenerati periodicamente

### 3. Rate Limiting
- **Tentativi di login**: Massimo 5 tentativi per IP in 15 minuti
- **Tabella login_attempts**: Registrazione di tutti i tentativi di accesso
- **Pulizia automatica**: Rimozione automatica dei tentativi vecchi

### 4. Gestione Sessioni Sicura
- **Sessioni HTTPOnly**: Cookie di sessione con flag HttpOnly
- **Sessioni Secure**: Cookie di sessione con flag Secure (HTTPS)
- **SameSite Strict**: Protezione contro attacchi CSRF
- **Regenerazione ID**: Rigenerazione periodica dell'ID di sessione
- **Timeout sessione**: Sessione scade dopo 1 ora di inattività

### 5. Validazione Input
- **Sanitizzazione**: Funzione `sanitizeInput()` per pulire tutti gli input
- **Validazione tipi**: Controllo dei tipi di dati con `filter_var()`
- **Validazione lunghezza**: Controllo della lunghezza degli input
- **Validazione formato**: Regex per validare formati specifici (email, date, etc.)

### 6. Password Security
- **Hashing sicuro**: Utilizzo di `password_hash()` con `PASSWORD_DEFAULT`
- **Requisiti password**: 
  - Minimo 8 caratteri
  - Almeno una lettera maiuscola
  - Almeno una lettera minuscola
  - Almeno un numero
  - Almeno un carattere speciale
- **Verifica password**: Utilizzo di `password_verify()`

### 7. Headers di Sicurezza
- **X-Content-Type-Options**: `nosniff`
- **X-Frame-Options**: `DENY`
- **X-XSS-Protection**: `1; mode=block`
- **Referrer-Policy**: `strict-origin-when-cross-origin`

### 8. Controllo Accessi
- **Autenticazione obbligatoria**: Verifica login su tutte le pagine protette
- **Autorizzazione**: Controllo dei permessi per ruolo utente
- **Isolamento dati**: Gli utenti possono accedere solo ai propri dati

### 9. Logging e Monitoraggio
- **Log tentativi login**: Registrazione di tutti i tentativi di accesso
- **Log errori**: Registrazione degli errori di sicurezza
- **IP tracking**: Tracciamento degli indirizzi IP per il rate limiting

### 10. Database Security
- **Indici di sicurezza**: Indici ottimizzati per le query di sicurezza
- **Pulizia automatica**: Eventi MySQL per pulire dati vecchi
- **Backup**: Raccomandazione di backup regolari

## File di Configurazione Sicurezza

### config.php
- Configurazioni di sicurezza centralizzate
- Funzioni di utilità per la sicurezza
- Inizializzazione sessioni sicure

### auth.php
- Funzioni di autenticazione e autorizzazione
- Controllo timeout sessioni
- Gestione permessi utente

## Tabelle Database Sicurezza

### login_attempts
```sql
CREATE TABLE login_attempts (
  id int(11) NOT NULL AUTO_INCREMENT,
  ip_address varchar(45) NOT NULL,
  username varchar(50) NOT NULL,
  success tinyint(1) NOT NULL DEFAULT 0,
  action varchar(20) NOT NULL DEFAULT 'login',
  attempt_time timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_ip_action_time (ip_address, action, attempt_time)
);
```

### Aggiornamenti users
- `created_at`: Timestamp di creazione account
- `last_login`: Ultimo accesso
- `failed_attempts`: Numero tentativi falliti
- `locked_until`: Blocco temporaneo account

## Raccomandazioni Aggiuntive

### Per Produzione
1. **HTTPS**: Utilizzare sempre HTTPS in produzione
2. **Firewall**: Configurare firewall per limitare accessi
3. **Backup**: Backup regolari del database
4. **Monitoraggio**: Implementare sistema di monitoraggio
5. **Updates**: Mantenere aggiornati PHP e MySQL

### Configurazione Server
```apache
# .htaccess per sicurezza aggiuntiva
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
```

### Variabili d'Ambiente
```php
// config.php - Per produzione
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
```




---

**Nota**: Questo documento deve essere aggiornato regolarmente con le nuove minacce e contromisure. 

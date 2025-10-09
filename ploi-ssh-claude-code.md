# Guida: Configurazione SSH con Ploi per Claude Code

## Introduzione

Questa guida ti aiuterà a configurare una chiave SSH per connetterti a un server gestito da Ploi e utilizzare Claude Code per lo sviluppo remoto. La documentazione si basa sulle guide ufficiali di Anthropic (Claude Code) e Ploi.

## Prerequisiti

- **Node.js 18 o superiore** - Richiesto per Claude Code
- **Account Claude.ai** (con Pro/Max) oppure **Account Claude Console** con billing attivo
- **Server gestito da Ploi** con accesso amministrativo
- **Terminale** (macOS/Linux) o **PuTTY** (Windows)
- Sistema operativo: macOS 10.15+, Ubuntu 20.04+/Debian 10+, o Windows 10+ (con WSL)

---

## Parte 1: Generazione della Chiave SSH

### Su macOS e Linux

1. **Genera una nuova chiave SSH**:
   ```bash
   ssh-keygen -t ed25519 -C "tua-email@esempio.com"
   ```
   
   Quando richiesto:
   - Premi Invio per accettare il percorso predefinito (`~/.ssh/id_ed25519`)
   - Inserisci una passphrase sicura (opzionale ma consigliato)

2. **Visualizza la tua chiave pubblica**:
   ```bash
   cat ~/.ssh/id_ed25519.pub
   ```

3. **Copia la chiave negli appunti** (macOS):
   ```bash
   pbcopy < ~/.ssh/id_ed25519.pub
   ```

### Su Windows

1. **Scarica e installa PuTTY** da https://www.putty.org/

2. **Apri PuTTYgen** (dal menu Start)

3. **Genera la coppia di chiavi**:
   - Assicurati che il tipo sia impostato su "RSA"
   - Clicca su "Generate"
   - Muovi il mouse nell'area grigia per generare casualità

4. **Salva la chiave privata**:
   - Clicca su "Save private key"
   - Salvala in un luogo sicuro (es. `C:\Users\TuoNome\.ssh\ploi_key.ppk`)

5. **Copia la chiave pubblica**:
   - Copia il testo dalla finestra "Public key" in PuTTYgen
   - **IMPORTANTE**: Usa questo testo, NON il file salvato con "Save public key"

---

## Parte 2: Aggiunta della Chiave SSH a Ploi

### Tramite Interfaccia Web

1. **Accedi al tuo account Ploi** su https://ploi.io

2. **Aggiungi la chiave al tuo profilo**:
   - Vai su Settings → SSH Keys
   - Clicca su "Add SSH Key"
   - Incolla la chiave pubblica copiata
   - Assegna un nome descrittivo (es. "MacBook Pro - 2025")
   - Salva

3. **Aggiungi la chiave al server specifico**:
   - Vai al tuo Server
   - Nella sezione "SSH Keys"
   - Clicca su "Add SSH Key"
   - Incolla la chiave pubblica
   - Specifica l'utente di sistema: `ploi` (predefinito)
   - Salva

### Tramite API Ploi (opzionale)

```bash
curl -X POST "https://ploi.io/api/servers/{server_id}/ssh-keys" \
  -H "Authorization: Bearer {tuo_token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  --data '{
    "name": "Chiave Locale",
    "key": "ssh-ed25519 AAAA...",
    "system_user": "ploi"
  }'
```

---

## Parte 3: Configurazione SSH Locale

### Configurazione per macOS (10.12+)

1. **Crea un file di configurazione SSH**:
   ```bash
   nano ~/.ssh/config
   ```

2. **Aggiungi la configurazione per il tuo server Ploi**:
   ```
   Host ploi-server
       HostName 123.45.67.89
       User ploi
       IdentityFile ~/.ssh/id_ed25519
       AddKeysToAgent yes
       UseKeychain yes
   
   Host *
       AddKeysToAgent yes
       UseKeychain yes
       IdentityFile ~/.ssh/id_ed25519
   ```

3. **Aggiungi la chiave all'agente SSH**:
   ```bash
   ssh-add ~/.ssh/id_ed25519
   ```

### Configurazione per Linux

1. **Crea il file di configurazione**:
   ```bash
   nano ~/.ssh/config
   ```

2. **Aggiungi la configurazione**:
   ```
   Host ploi-server
       HostName 123.45.67.89
       User ploi
       IdentityFile ~/.ssh/id_ed25519
       AddKeysToAgent yes
   
   Host *
       AddKeysToAgent yes
       IdentityFile ~/.ssh/id_ed25519
   ```

3. **Avvia l'agente SSH e aggiungi la chiave**:
   ```bash
   eval "$(ssh-agent -s)"
   ssh-add ~/.ssh/id_ed25519
   ```

### Configurazione per Windows

1. **Configura PuTTY**:
   - Apri PuTTY
   - In "Session":
     - Host Name: `ploi@123.45.67.89`
     - Port: `22`
   - In "Connection → SSH → Auth":
     - Browse e seleziona il file `.ppk` salvato
   - In "Session":
     - Salva con un nome (es. "Ploi Server")

---

## Parte 4: Test della Connessione

### Test Base

```bash
# Su macOS/Linux (usando l'alias configurato)
ssh ploi-server

# Oppure con il comando completo
ssh ploi@123.45.67.89

# Su Windows: apri la sessione salvata in PuTTY
```

### Verifica che la connessione funzioni

Dovresti vedere qualcosa come:
```
Welcome to Ubuntu 22.04 LTS
Last login: Wed Oct 08 10:30:00 2025 from 192.168.1.100
ploi@server:~$
```

### Accesso all'utente Root (se necessario)

```bash
sudo su
# Inserisci la password ricevuta via email durante l'installazione del server
```

---

## Parte 5: Installazione e Configurazione di Claude Code

### Installazione

```bash
# Installa Claude Code globalmente
npm install -g @anthropic-ai/claude-code

# Verifica l'installazione
claude --version
```

### Primo Utilizzo

1. **Avvia Claude Code**:
   ```bash
   cd /percorso/del/tuo/progetto
   claude
   ```

2. **Autenticazione**:
   - Al primo utilizzo ti verrà chiesto di effettuare il login
   - Segui le istruzioni per autenticarti con il tuo account Claude

### Configurazione Modello (opzionale)

```bash
# Usa Claude Sonnet 4.5 (consigliato per bilanciare prestazioni e velocità)
export ANTHROPIC_MODEL="claude-sonnet-4-5-20250929"

# Oppure usa Claude 4.1 Opus per capacità massime
export ANTHROPIC_MODEL="claude-4-1-opus-20250514"
```

Aggiungi questa riga al tuo `~/.bashrc` o `~/.zshrc` per renderla permanente.

---

## Parte 6: Utilizzo di Claude Code con SSH Remoto

### Scenario 1: Lavorare Direttamente sul Server

```bash
# Connettiti al server Ploi
ssh ploi-server

# Naviga al tuo progetto
cd /percorso/del/progetto

# Avvia Claude Code
claude
```

### Scenario 2: Tunnel SSH per Accesso Locale

Questo metodo ti permette di eseguire Claude Code sul server remoto ma controllarlo dal tuo terminale locale.

1. **Crea un alias nel tuo `~/.bashrc` o `~/.zshrc`**:
   ```bash
   alias claude-remote='ssh -q -o LogLevel=quiet -L 8082:localhost:8080 ploi@123.45.67.89 -t "cd /percorso/progetto && claude; bash"'
   ```

2. **Usa l'alias**:
   ```bash
   claude-remote
   ```

### Scenario 3: Utilizzo con tmux per Persistenza

Utile per mantenere Claude Code in esecuzione anche se ti disconnetti.

1. **Sul server, installa tmux** (se non già presente):
   ```bash
   sudo apt install tmux
   ```

2. **Crea una sessione tmux**:
   ```bash
   tmux new -s claude-session
   ```

3. **Avvia Claude Code nella sessione**:
   ```bash
   cd /percorso/progetto
   claude
   ```

4. **Disconnetti dalla sessione** (senza chiuderla):
   - Premi `Ctrl+B`, poi `D`

5. **Riconnettiti alla sessione**:
   ```bash
   ssh ploi-server
   tmux attach -t claude-session
   ```

### Scenario 4: Accesso da Mobile (con Tailscale)

Per controllare Claude Code dal tuo smartphone:

1. **Installa Tailscale** sul server e sul dispositivo mobile
   
2. **Sul server**:
   ```bash
   curl -fsSL https://tailscale.com/install.sh | sh
   sudo tailscale up
   ```

3. **Sul mobile**:
   - Installa l'app Tailscale
   - Accedi con lo stesso account

4. **Usa un client SSH mobile** (es. Termius, Blink) con l'IP Tailscale del server

---

## Parte 7: Gestione dei Permessi per Claude Code

### Configurazione UI dei Permessi

```bash
# Avvia l'interfaccia interattiva dei permessi
claude
/permissions
```

Questo apre un'interfaccia dove puoi:
- Autorizzare/negare specifici strumenti
- Configurare i permessi per directory
- Gestire l'accesso ai file

### Configurazione Manuale

Crea o modifica `~/.claude.json`:

```json
{
  "allowedTools": [
    "bash",
    "read_file",
    "write_file",
    "list_directory",
    "create_directory"
  ],
  "deniedTools": [
    "delete_file"
  ],
  "allowedDirectories": [
    "/home/ploi/progetti/app",
    "/var/www/sito"
  ]
}
```

---

## Parte 8: Risoluzione Problemi Comuni

### Errore: "Permission denied (publickey)"

**Causa**: La chiave SSH non è configurata correttamente.

**Soluzione**:
```bash
# Verifica che la chiave sia nell'agente SSH
ssh-add -l

# Se non presente, aggiungila
ssh-add ~/.ssh/id_ed25519

# Verifica che la chiave pubblica sia sul server
ssh ploi@123.45.67.89 "cat ~/.ssh/authorized_keys"
```

### Errore: "Could not resolve hostname"

**Causa**: L'indirizzo IP o hostname è errato.

**Soluzione**:
- Verifica l'IP del server nel pannello Ploi
- Controlla il file `~/.ssh/config`

### Claude Code non riconosce i prompt interattivi SSH

**Problema**: Claude Code ha difficoltà con prompt interattivi come le passphrase SSH.

**Soluzione**:
1. Usa chiavi SSH senza passphrase per Claude Code
2. Oppure aggiungi la chiave all'agente SSH prima di avviare Claude Code:
   ```bash
   ssh-add ~/.ssh/id_ed25519
   claude
   ```

### Timeout di connessione

**Soluzione**:
```bash
# Aggiungi al tuo ~/.ssh/config
Host *
    ServerAliveInterval 60
    ServerAliveCountMax 3
```

---

## Parte 9: Best Practices di Sicurezza

### Protezione delle Chiavi

1. **Non condividere mai la chiave privata**
2. **Usa passphrase per le chiavi** (quando possibile)
3. **Mantieni permessi corretti**:
   ```bash
   chmod 600 ~/.ssh/id_ed25519
   chmod 644 ~/.ssh/id_ed25519.pub
   chmod 700 ~/.ssh
   ```

### Configurazione Firewall

Su macOS, limita SSH solo a Tailscale (se usato):
- System Settings → Security & Privacy → Firewall
- Configura per accettare SSH solo dall'interfaccia Tailscale

### Rotazione delle Chiavi

Cambia le tue chiavi SSH periodicamente:
1. Genera nuova chiave
2. Aggiungi a Ploi
3. Testa la connessione
4. Rimuovi la vecchia chiave da Ploi

### Monitoring degli Accessi

```bash
# Verifica gli ultimi accessi SSH
last -a | head -20

# Controlla i tentativi falliti
sudo grep "Failed password" /var/log/auth.log
```

---

## Parte 10: Comandi Utili per Claude Code

### Comandi Base

```bash
# Avvia Claude Code in modalità interattiva
claude

# Esegui un comando singolo
claude -p "Correggi i problemi di lint nel progetto"

# Passa input via pipe
cat errore.log | claude -p "Analizza questo errore"

# Modalità headless (senza interazione)
claude -p "Scrivi test per il file auth.js" --headless
```

### Gestione Directory Multiple

```bash
# Aggiungi directory durante l'esecuzione
claude --add-dir /percorso/altra/directory

# Durante la sessione
/add-dir /percorso/altra/directory
```

### Configurazione Avanzata

```bash
# Usa un modello specifico per una sessione
ANTHROPIC_MODEL="claude-4-1-opus-20250514" claude

# Cambia limite di token
claude --max-tokens 4000
```

---

## Risorse Aggiuntive

### Documentazione

- **Claude Code**: https://docs.claude.com/en/docs/claude-code/overview
- **Ploi SSH**: https://ploi.io/documentation/ssh
- **Tailscale**: https://tailscale.com/kb/

### Supporto

- **Ploi Support**: https://ploi.io/support
- **Anthropic Support**: Per problemi con Claude Code, usa il feedback nell'applicazione
- **Community**: Forum e Discord di entrambe le piattaforme

---

## Checklist Rapida di Setup

- [ ] Chiave SSH generata
- [ ] Chiave pubblica aggiunta al profilo Ploi
- [ ] Chiave aggiunta al server specifico
- [ ] File `~/.ssh/config` configurato
- [ ] Connessione SSH testata con successo
- [ ] Node.js 18+ installato
- [ ] Claude Code installato globalmente
- [ ] Primo login a Claude Code completato
- [ ] Permessi configurati (se necessario)
- [ ] Test di Claude Code sul server completato

---

*Ultima modifica: Ottobre 2025*
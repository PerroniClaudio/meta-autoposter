# 📱 Meta AutoPoster - Guida Completa

Siamo tutti d'accordo che Facebook è una piattaforma infernale, pertanto compilerò questa guida pratica per sopravvivere al labirinto delle API di Meta.

## 🎥 Video Tutorial di Riferimento

https://www.youtube.com/watch?v=3HvzgDzrG0c

---

## 🚀 Setup Iniziale

### 1. Installazione

```bash
git clone <your-repo>
cd meta-autoposter
composer install
npm install
cp .env.example .env
php artisan key:generate
```

---

## 🔐 Configurazione Facebook/Meta API

### Step 1: Creare un'App Facebook

1. Vai su https://developers.facebook.com/
2. Clicca su "My Apps" > "Create App"
3. Seleziona "Business" come tipo di app
4. Compila i dettagli dell'app
5. Una volta creata, vai su "Settings" > "Basic"

**Ottieni questi valori:**

-   `META_APP_ID` = App ID
-   `META_APP_SECRET` = App Secret

### Step 2: Configurare i Prodotti

1. Nel dashboard dell'app, vai su "Products"
2. Aggiungi questi prodotti:
    - **Pages API**
    - **Instagram API** (se necessario)

### Step 3: Ottenere i Permessi

Nel "App Review" richiedi questi permessi:

-   `pages_manage_posts` - Per creare post
-   `pages_read_engagement` - Per leggere metriche
-   `pages_show_list` - Per ottenere lista pagine
-   `business_management` (se necessario)

---

## 🔑 Ottenere i Token di Accesso

### Metodo Raccomandato: Graph API Explorer

1. Vai su https://developers.facebook.com/tools/explorer/
2. Seleziona la tua app
3. Richiedi questi permessi:
    - `pages_manage_posts`
    - `pages_read_engagement`
    - `pages_show_list`
4. Clicca "Generate Access Token"
5. **IMPORTANTE**: Converti in Long-Lived Token:
    ```
    GET https://graph.facebook.com/oauth/access_token?grant_type=fb_exchange_token&client_id={META_APP_ID}&client_secret={META_APP_SECRET}&fb_exchange_token={SHORT_LIVED_TOKEN}
    ```

---

## 📄 Ottenere l'ID della Pagina Facebook

### Metodo 1: Dall'URL della Pagina

1. Vai sulla tua pagina Facebook
2. Clicca su "About"
3. Scorri fino in fondo, troverai "Page ID"

### Metodo 2: Tramite API

```bash
curl -X GET "https://graph.facebook.com/me/accounts?access_token=YOUR_ACCESS_TOKEN"
```

### Metodo 3: Nel tuo Controller

```php
$facebookController = new FacebookController();
$pages = $facebookController->getUserPages();
```

---

---

## 📸 Ottenere l'ID dell'Account Instagram Business

L'ID dell'Account Instagram Business è un identificatore numerico univoco per il tuo profilo Instagram professionale. **Non è il tuo username (@tuonome) e non è l'ID della tua App Meta.** È fondamentale per utilizzare la Graph API di Instagram.

Se riscontri problemi nel recuperarlo, la causa è quasi sempre legata ai permessi del token di accesso.

### Metodo 1: Graph API Explorer (Consigliato)

Questo è il metodo più diretto e affidabile.

1.  Vai al [**Graph API Explorer di Meta**](https://developers.facebook.com/tools/explorer/).
2.  A destra, nel menu a discesa **"Application"**, seleziona la tua app Meta.
3.  Sotto, nel menu **"User or Page"**, seleziona **"Get Page Access Token"**.
4.  Assicurati che la tua Pagina Facebook (quella collegata al tuo account Instagram) sia selezionata.
5.  Clicca sulla tab **"Add a Permission"** e assicurati di avere selezionato **TUTTI** i seguenti permessi:
    -   `pages_show_list`
    -   `pages_read_engagement`
    -   `instagram_basic`
    -   `business_management` (Spesso risolutivo per problemi di visibilità)
6.  Clicca su **"Generate Access Token"**.
7.  Nel campo della query, inserisci questa richiesta, sostituendo `{your-facebook-page-id}` con l'ID della tua Pagina Facebook:
    ```
    GET {your-facebook-page-id}?fields=instagram_business_account
    ```
8.  Clicca su **"Submit"**. La risposta sarà simile a questa:

    ```json
    {
        "instagram_business_account": {
            "id": "17841405822333333" // <-- QUESTO È L'ID CHE CERCHI
        },
        "id": "721065034421034"
    }
    ```

9.  Copia l'ID numerico (`1784...`) e inseriscilo nel tuo file `.env` come `INSTAGRAM_BUSINESS_ACCOUNT_ID`.

### Metodo 2: Tramite il Controller

Puoi usare il metodo helper già presente nel `FacebookController`.

1.  Crea una rotta di test temporanea in `routes/web.php`:
    ```php
    Route::get('/get-ig-id', [App\Http\Controllers\FacebookController::class, 'getInstagramBusinessAccountId']);
    ```
2.  Visita `http://tuo-dominio.test/get-ig-id` nel browser.
3.  La risposta JSON conterrà l'ID, se trovato.

### Metodo 3: Verifica in Meta Business Suite

Utile per confermare che il collegamento tra Pagina e Account Instagram sia corretto.

1.  Vai su [business.facebook.com/settings/](https://business.facebook.com/settings/).
2.  Naviga in `Accounts` -> `Instagram accounts`.
3.  Seleziona il tuo account Instagram e vai su **"Connected assets"** per verificare che la tua Pagina Facebook sia correttamente collegata.

---

## ⚙️ Configurazione File .env

Copia i valori ottenuti nel tuo file `.env`:

```bash
# Meta App Configuration
META_APP_ID=your_app_id_here
META_APP_SECRET=your_app_secret_here
META_ACCESS_TOKEN=your_access_token_here

# Facebook Page
FACEBOOK_PAGE_ID=your_page_id_here

# Services Configuration
FACEBOOK_APP_ID="${META_APP_ID}"
FACEBOOK_APP_SECRET="${META_APP_SECRET}"
FACEBOOK_TOKEN="${META_ACCESS_TOKEN}"
```

---

## 🛠️ Configurazione Laravel Services

Aggiungi in `config/services.php`:

```php
'facebook' => [
    'app_id' => env('FACEBOOK_APP_ID'),
    'app_secret' => env('FACEBOOK_APP_SECRET'),
    'token' => env('FACEBOOK_TOKEN'),
],
```

---

## 🧪 Testing della Configurazione

### 1. Verifica le Pagine Disponibili

```php
$controller = new FacebookController();
$pages = $controller->getUserPages();
```

### 2. Verifica i Permessi

```php
$controller = new FacebookController();
$permissions = $controller->checkPagePermissions('YOUR_PAGE_ID');
```

### 3. Crea un Post di Test

```php
$controller = new FacebookController();
$result = $controller->createPageFeedPost('YOUR_PAGE_ID', 'Test post from Laravel!');
```

---

## 🚨 Troubleshooting Comune

### Errore "(#100) Only owners of the URL..."

-   **Causa**: Stai cercando di personalizzare metadati per URL esterni
-   **Soluzione**: Non usare parametri `picture`, `name`, `thumbnail`, `description` per URL che non possiedi

### Errore "Invalid OAuth Access Token"

-   **Causa**: Token scaduto o non valido
-   **Soluzione**: Rigenera un Long-Lived Access Token

### Errore "Insufficient Permissions"

-   **Causa**: L'app non ha i permessi necessari
-   **Soluzione**: Verifica i permessi in App Review

### Errore "Page Access Token Not Found"

-   **Causa**: Non hai accesso amministrativo alla pagina
-   **Soluzione**: Assicurati di essere admin della pagina Facebook

---

## 📚 Metodi Disponibili nel Controller

### 📄 Gestione Feed

-   `getPageFeed($pageId)` - Ottieni il feed della pagina
-   `createPageFeedPost($pageId, $message)` - Crea un post
-   `createPageFeedPostWithImage($pageId, $message, $callToAction)` - Post con CTA

### 📸 Gestione Foto

-   `getPagePhotos($pageId)` - Ottieni le foto della pagina
-   `createPagePhoto($pageId, $imageUrl)` - Carica una foto

### 👥 Gestione Pagina

-   `getUserPages()` - Ottieni tutte le tue pagine
-   `getPageRoles($pageId)` - Ottieni i ruoli della pagina
-   `checkPagePermissions($pageId)` - Verifica i permessi

### 🔧 Helper Methods

-   `getPageAccessToken($pageId)` - Ottieni token specifico della pagina
-   `setPageAccessToken($pageId)` - Imposta token per la pagina
-   `createProductCarousel($pageId, $message, $products)` - Crea carousel prodotti

---

## 📝 Log e Debugging

I log sono configurati per essere salvati in `storage/logs/laravel.log`.

Visualizza i log in tempo reale:

```bash
tail -f storage/logs/laravel.log
```

Cerca log specifici:

```bash
grep "Creating post" storage/logs/laravel.log
```

---

## 🔄 Rinnovo Token

I Long-Lived Access Token scadono dopo ~60 giorni. Per rinnovarli automaticamente, implementa un sistema di refresh o configura webhook per monitorare le scadenze.

---

## 📞 Supporto

Se incontri problemi:

1. Controlla i log in `storage/logs/laravel.log`
2. Verifica la configurazione in `config/services.php`
3. Testa i permessi con `checkPagePermissions()`
4. Consulta la [documentazione ufficiale di Meta](https://developers.facebook.com/docs/)

---

**Buona fortuna nel navigare l'inferno di Facebook! 🔥**

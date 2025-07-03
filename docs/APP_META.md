## 🔐 Configurazione delle API Meta

Per far funzionare l'applicazione, devi ottenere alcune credenziali dalla piattaforma per sviluppatori di Meta.

### Step 1: Creare un'App Meta

1.  Vai su [Meta for Developers](https://developers.facebook.com/) e crea una nuova App di tipo **Business**.
2.  Dal menu laterale, vai su **App Settings > Basic**.
3.  Recupera i seguenti valori e inseriscili nel tuo file `.env`:
    -   `META_APP_ID`
    -   `META_APP_SECRET`

### Step 2: Aggiungere Prodotti e Permessi

1.  Nel pannello della tua app, vai su **Products**.
2.  Aggiungi i seguenti prodotti:
    -   **Instagram Graph API**
    -   **Facebook Graph API** (solitamente già attiva)
3.  Successivamente, devi ottenere un **Token di Accesso Utente** con i permessi corretti. Vai su **Tools > Graph API Explorer**.
4.  Seleziona la tua App dal menu a discesa.
5.  Nel menu "Permissions", aggiungi **tutti** i seguenti permessi:
    -   `pages_show_list`
    -   `pages_read_engagement`
    -   `pages_manage_posts`
    -   `instagram_basic`
    -   `instagram_content_publish`
    -   `business_management`
6.  Clicca su **Generate Access Token**.

### Step 3: Ottenere un Token "Long-Lived"

Il token generato dura solo un'ora. Devi scambiarlo con uno a lunga durata (~60 giorni). Esegui questa chiamata (puoi usare il browser o Postman):

```
https://graph.facebook.com/oauth/access_token?grant_type=fb_exchange_token&client_id={META_APP_ID}&client_secret={META_APP_SECRET}&fb_exchange_token={TOKEN_A_BREVE_DURATA}
```

Il risultato conterrà un `access_token`. Copialo e incollalo nel tuo file `.env` come `META_ACCESS_TOKEN`.

### Step 4: Ottenere gli ID Necessari

1.  **Facebook Page ID (`FACEBOOK_PAGE_ID`)**:

    -   Vai sulla tua Pagina Facebook, nella sezione "About" > "Page transparency". L'ID è elencato lì.

2.  **Instagram Business Account ID (`INSTAGRAM_BUSINESS_ACCOUNT_ID`)**:
    -   Questo è l'ID più importante e difficile da trovare. Usa il **Graph API Explorer** (con il token che hai appena generato).
    -   Esegui questa query, sostituendo `{FACEBOOK_PAGE_ID}` con l'ID della tua pagina collegata a Instagram:
        ```
        GET {FACEBOOK_PAGE_ID}?fields=instagram_business_account
        ```
    -   La risposta conterrà l'ID che ti serve.

---

## ⚙️ Configurazione File `.env`

Il tuo file `.env` dovrebbe assomigliare a questo:

```dotenv
# --- META APP ---
META_APP_ID=your_app_id
META_APP_SECRET=your_app_secret
META_ACCESS_TOKEN=your_long_lived_access_token

# --- FACEBOOK ---
FACEBOOK_PAGE_ID=your_facebook_page_id

# --- INSTAGRAM ---
# L'ID e il Secret di Instagram sono spesso diversi da quelli di Meta.
# Se sono uguali, puoi anche usare le variabili META_.
INSTAGRAM_APP_ID=your_instagram_app_id
INSTAGRAM_APP_SECRET=your_instagram_app_secret
INSTAGRAM_BUSINESS_ACCOUNT_ID=your_instagram_business_account_id
```

---

## 🚀 Come Pubblicare Contenuti

Tutta la logica di pubblicazione è gestita dai controller `FacebookController` e `InstagramController`. Puoi usarli nelle tue rotte, comandi o job.

**Importante:** Per le immagini e i video, devi usare **URL pubblici e diretti**. I servizi che usano reindirizzamenti (come `picsum.photos`) non funzioneranno.

### Esempi di utilizzo in `routes/web.php`

```php
use App\Http\Controllers\FacebookController;
use App\Http\Controllers\InstagramController;
use Illuminate\Support\Facades\Route;

// --- Esempi per Facebook ---

// Pubblicare un post di testo
Route::get('/post-fb-text', function () {
    $controller = new FacebookController();
    return $controller->createPost(config('services.facebook.page_id'), 'Questo è un post di testo da Laravel!');
});

// Pubblicare una foto
Route::get('/post-fb-photo', function () {
    $controller = new FacebookController();
    $imageUrl = 'https://i.imgur.com/1bX5QH6.jpg'; // URL diretto
    return $controller->createPhotoPost(config('services.facebook.page_id'), $imageUrl, 'Didascalia della foto!');
});


// --- Esempi per Instagram ---

// Pubblicare un'immagine
Route::get('/post-ig-image', function () {
    $controller = new InstagramController();
    $imageUrl = 'https://i.imgur.com/1bX5QH6.jpg';
    return $controller->createAndPublishImage($imageUrl, 'Didascalia per Instagram!');
});

// Pubblicare un video (richiede più tempo)
Route::get('/post-ig-video', function () {
    $controller = new InstagramController();
    $videoUrl = 'http://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerFun.mp4';
    return $controller->createAndPublishVideo($videoUrl, 'Un bel video da Laravel!');
});

// Pubblicare una Storia (immagine)
Route::get('/post-ig-story', function () {
    $controller = new InstagramController();
    $imageUrl = 'https://i.imgur.com/1bX5QH6.jpg';
    return $controller->createAndPublishStory($imageUrl, 'IMAGE');
});

// Pubblicare un Reel
Route::get('/post-ig-reel', function () {
    $controller = new InstagramController();
    $videoUrl = 'http://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerFun.mp4';
    return $controller->createAndPublishReel($videoUrl, 'Il mio primo Reel da API!', true);
});
```

---

## 📚 Metodi Disponibili nei Controller

### FacebookController

-   `createPost($pageId, $message, $link = null)`: Crea un post di testo o con un link.
-   `createPhotoPost($pageId, $imageUrl, $caption = null)`: Pubblica una foto da un URL.

### InstagramController

-   `createAndPublishImage($imageUrl, $caption = '')`: Pubblica un'immagine.
-   `createAndPublishVideo($videoUrl, $caption = '')`: Pubblica un video.
-   `createAndPublishStory($mediaUrl, $mediaType = 'IMAGE')`: Pubblica una Storia (immagine o video).
-   `createAndPublishReel($videoUrl, $caption = '', $shareToFeed = false)`: Pubblica un Reel, con opzione per condividerlo nel feed.

---

## 📝 Log e Debugging

Ogni operazione viene registrata in `storage/logs/laravel.log`. Per monitorare le attività in tempo reale:

```bash
tail -f storage/logs/laravel.log
```

---

## 🚨 Troubleshooting

-   **Errore di URL non valido**: Assicurati che gli URL di immagini e video siano diretti, pubblici e non utilizzino reindirizzamenti.
-   **Errore di Permessi Insufficienti**: Verifica di aver concesso tutti i permessi elencati nello Step 2 e di usare un token di accesso valido.
-   **Token Scaduto**: I token "Long-Lived" durano circa 60 giorni. Dovrai rigenerarli manualmente o implementare un job che gestisca il refresh.

---

**Buona fortuna nel navigare l'inferno di Facebook! 🔥**

Siamo tutti d'accordo che Facebook è una piattaforma infernale, pertanto ho fatto questa guida pratica per sopravvivere al labirinto delle API di Meta.

## 🎥 Video Tutorial di Riferimento

https://www.youtube.com/watch?v=3HvzgDzrG0c

---

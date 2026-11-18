# Point of View 🎬

**Point of View** is an entertainment community website where people who watch movies and series and read anime, manga and books share reviews and opinions, then discuss them together.

This repository holds the **frontend only**. It is built with Laravel (Blade), Bootstrap 5, vanilla JavaScript and custom CSS. All content is static mock data, and the backend (MySQL, authentication, posting) will be connected later.

---

## Pages

| URL         | Page                         | View file                                |
|-------------|------------------------------|------------------------------------------|
| `/`         | Homepage (landing page)      | `resources/views/home.blade.php`         |
| `/login`    | Login (card opens on Login)  | `resources/views/auth/login.blade.php`   |
| `/register` | Same card, opens on Sign up  | `resources/views/auth/login.blade.php`   |
| `/feed`     | Community feed (user side)   | `resources/views/feed.blade.php`         |

---

## How to run

You need **PHP 8.3+** and **Composer**. Node/npm is **not** needed, because CSS and JS are plain files in `public/`.

```bash
composer install
cp .env.example .env        # only on a fresh clone
php artisan key:generate    # only on a fresh clone
php artisan serve
```

Then open <http://127.0.0.1:8000>.

Run the tests:

```bash
php artisan test
```

> Sessions use the `file` driver, so the pages work even while MySQL is not set up yet.

---

## Folder structure

```
app/
├── Http/Controllers/
│   ├── PageController.php       # home() and feed(): pass mock data to the views
│   └── AuthController.php       # showLogin() / showRegister() (UI only)
├── Support/
│   └── MockData.php             # ALL static data (posters, posts, users, stats…)
└── View/Components/
    └── PosterBackground.php     # builds the rows for the animated poster wall

resources/views/
├── layouts/app.blade.php        # shared layout (head, preloader, navbar, footer, toasts)
├── partials/                    # navbar, footer, preloader
├── components/                  # poster-background, poster-card, post-card, category-card
├── home.blade.php
├── auth/login.blade.php
└── feed.blade.php

public/
├── css/
│   ├── variables.css            # 🎨 theme colors, fonts, radius, motion (edit here!)
│   ├── base.css                 # typography, film grain, helpers
│   ├── components.css           # navbar, buttons, cards, forms, toasts, poster wall…
│   ├── animations.css           # all @keyframes, reveal effects, reduced motion
│   ├── intro.css                # "Enter Point of View" overlay, fallback, feed arrival
│   └── pages/ home.css · auth.css · feed.css
├── js/
│   ├── main.js                  # preloader, navbar, cursor, ripple, toasts, likes, filters
│   ├── animations.js            # scroll reveal, 3D tilt, typing text, counters, carousel
│   ├── auth.js                  # login ⇄ register flip, validation, password tools
│   ├── intro.js                 # 🎬 "Enter Point of View" sequence (Three.js) + INTRO_CONFIG
│   ├── intro-sound.js           # 🔊 Web Audio sound design for the intro
│   └── feed.js                  # composer, tabs, comments, follow, chat panel
├── images/posters/              # 🖼️ drop your own poster images here
├── images/intro/                # optional joker.png for the account card
└── audio/                       # optional whoosh.mp3 / hit.mp3 for the intro
```

---

## Changing the theme colors

Every color is a CSS variable in `public/css/variables.css`:

```css
:root {
    --pov-midnight: #070B1F;   /* main background */
    --pov-orange:   #FF7A2F;   /* primary accent / buttons */
    --pov-pink:     #FF5E62;   /* gradient partner */
    /* … */
}
```

Glows, borders and transparent tints are generated with `color-mix()`, so changing one color updates them all.

---

## Using your own poster images

The animated background shows famous Japanese films and anime. Because real posters are copyrighted, each card shows a **gradient placeholder with the title** by default.

To use real images, put a file in **`public/images/posters/`** named after the poster's *slug*. You can use `.webp`, `.jpg`, `.jpeg` or `.png`. The card picks it up automatically and the gradient is used only when no file exists.

| Title                          | File name (any of the extensions above) |
|--------------------------------|------------------------------------------|
| Your Name                      | `your-name.jpg`                          |
| Spirited Away                  | `spirited-away.jpg`                      |
| Demon Slayer                   | `demon-slayer.jpg`                       |
| Attack on Titan                | `attack-on-titan.jpg`                    |
| Weathering With You            | `weathering-with-you.jpg`                |
| Akira                          | `akira.jpg`                              |
| Princess Mononoke              | `princess-mononoke.jpg`                  |
| Josee, the Tiger and the Fish  | `josee-the-tiger-and-the-fish.jpg`       |
| Suzume                         | `suzume.jpg`                             |
| Jujutsu Kaisen                 | `jujutsu-kaisen.jpg`                     |
| One Piece                      | `one-piece.jpg`                          |
| Death Note                     | `death-note.jpg`                         |
| Howl's Moving Castle           | `howls-moving-castle.jpg`                |
| Perfect Blue                   | `perfect-blue.jpg`                       |
| A Silent Voice                 | `a-silent-voice.jpg`                     |
| Spy x Family                   | `spy-x-family.jpg`                       |

Tips: use a portrait ratio of about **2:3** (for example 400×600), and keep each file under ~150 KB (`.webp` is best) so the page stays fast. To add or rename titles, edit `MockData::posters()`.

---

## 🎬 "Enter Point of View" intro sequence

When the Login or Register form passes validation, an ~8.5 second cinematic plays, then the app goes to `/feed`:

| Time       | What happens                                                                 |
|------------|------------------------------------------------------------------------------|
| 0.0–0.8s   | Login card shrinks, poster cards lift off the background wall               |
| 0.8–3.5s   | Camera dives into a spiral tunnel of poster cards, embers streak past        |
| 3.5–5.0s   | The glowing **POV card** flips in the centre (slow motion, heartbeat)        |
| 5.0–6.0s   | The tunnel bursts and cards float in 3D space with depth of field           |
| 6.0–7.5s   | Your **account card** (avatar + `@username`) flies to the camera            |
| 7.5–8.5s   | Zoom, the feed layout appears behind the card, orange flash → `/feed`       |

On `/feed` the flash opens like an iris ("Welcome in, @username") while the feed fades in.

**How it is built:**
- Three.js `0.160.0` is loaded from the jsDelivr CDN only when it is needed. The page starts downloading it as soon as the user focuses the form.
- All poster cards are drawn by one `InstancedMesh` using a single canvas texture atlas, so no extra image files are needed.
- Sounds are generated with the Web Audio API in `intro-sound.js`. Master volume is 0.3.

**Controls:**
- **Skip** button (or the `Esc` key) jumps straight to the feed.
- 🔊 button mutes or unmutes the sound. It is on the login page and inside the intro, and the choice is saved in `localStorage`.

**Plays once per login:** a `sessionStorage` flag stops it from replaying. Clicking **Log out** in the navbar resets the flag.

**Fallbacks:**
- **No WebGL / Three.js fails to load:** a lighter CSS-3D version plays instead.
- **`prefers-reduced-motion`** (or the intro already played): a quick 300 ms fade is shown instead of the full sequence.

### Tweaking it

| What                         | Where                                                                              |
|------------------------------|------------------------------------------------------------------------------------|
| Phase timings, cue points    | `INTRO_CONFIG.phases` / `.cues` at the top of `public/js/intro.js`                 |
| Sound timings                | `INTRO_CONFIG.sound` (same file), so sound always matches the visuals              |
| Tunnel speed / spin          | `INTRO_CONFIG.motion`, `INTRO_CONFIG.tunnel`                                       |
| Card / ember counts, quality | `INTRO_CONFIG.quality.desktop` / `.mobile`                                         |
| Depth-of-field blur          | `INTRO_CONFIG.dof`                                                                 |
| Volume, reverb               | `SOUND_CONFIG` at the top of `public/js/intro-sound.js`                            |
| Colors                       | `public/css/variables.css`. The intro reads `--pov-midnight`, `--pov-orange`, etc. at runtime |

### Custom images and audio (optional)

| File                                   | Effect                                                    |
|----------------------------------------|-----------------------------------------------------------|
| `public/images/posters/{slug}.jpg`     | Real poster on the tunnel cards (see the table above)     |
| `public/images/intro/joker.png`        | Shown on the face of the account card (portrait, 2:3)     |
| `public/audio/whoosh.mp3`              | Replaces the synthesized tunnel whoosh and burst          |
| `public/audio/hit.mp3`                 | Replaces the final impact at the flash                    |

The server only tells the browser about files that exist (`app/Support/IntroAssets.php`), so missing files never cause 404 errors.

### Testing / debugging

- **Replay the intro anytime:** open `/login?intro=replay`.
- **Hold one moment of the timeline:** `/login?intro=replay&introFreeze=4.3` (any second), then submit the form. Useful for tuning.
- **Preview the no-WebGL version:** `/login?intro=replay&introFallback=css`.
- **Call it from your own code:**
  ```js
  POV.playEnterSequence({ username: 'hana', avatar: null, onComplete: () => location.href = '/feed' });
  ```

---

## Connecting the backend later

1. **Database (MySQL):** set your credentials in `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=entertainment_community_database
   DB_USERNAME=root
   DB_PASSWORD=your_password
   ```
   Then create the tables with `php artisan migrate`.
2. **Replace the mock data:** each method in `app/Support/MockData.php` has a `TODO (Backend)` note showing the matching Eloquent query. Swap the calls in `PageController` for real queries (e.g. `Post::with('user')->latest()->paginate(10)`).
3. **Authentication:** see the `TODO (Backend)` notes in `routes/web.php`, `AuthController.php` and `auth/login.blade.php`. Add `POST /login` and `POST /register` routes, give the forms `method="POST"`, an `action` and `@csrf`. In `public/js/auth.js`, send the form with `fetch()` and call `POV.playEnterSequence()` **only after a successful response** (a TODO marks the spot). You can also install Laravel Breeze or Fortify.
4. **Protect the feed:** add `->middleware('auth')` to the `/feed` route, and replace the mocked signed-in navbar with `@auth` / `@guest`.
5. **Likes, comments, posts, follows:** the JS files contain `TODO (Backend)` comments showing where to `fetch()` your Laravel routes. The CSRF token is already in `<meta name="csrf-token">`.

Search the project for `TODO (Backend)` to find every connection point.

---

## Accessibility & performance notes

- Semantic landmarks, labelled buttons and inputs, visible focus outlines and a "Skip to content" link.
- `prefers-reduced-motion` stops the poster wall, film grain and the other looping animations.
- The poster wall only animates `transform` (GPU friendly) and pauses when scrolled off-screen.
- The custom cursor and 3D tilt are turned off on touch devices.

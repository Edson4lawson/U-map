# 🚀 Guide Configuration Render + Vercel - Étape par Étape

Ce guide vous explique exactement quoi configurer sur Render et Vercel après les modifications.

---

## 📦 PARTIE 1 : RENDER (Backend)

### Étape 1 : Connectez-vous à Render

1. Allez sur [render.com](https://render.com)
2. Connectez-vous avec votre compte
3. Ouvrez votre projet U-Map Backend

### Étape 2 : Ajouter les variables d'environnement

Allez dans : **Dashboard > Votre Service > Environment Variables**

Cliquez sur **"Add Environment Variable"** pour chaque variable ci-dessous :

#### Variable 1 : BROADCAST_CONNECTION
- **Name :** `BROADCAST_CONNECTION`
- **Value :** `reverb`

#### Variable 2 : REVERB_APP_ID
- **Name :** `REVERB_APP_ID`
- **Value :** `umap-reverb`

#### Variable 3 : REVERB_APP_KEY
- **Name :** `REVERB_APP_KEY`
- **Value :** `GÉNÉREZ_VOTRE_PROPRE_CLÉ_ICI`
- **Note :** Générez avec : `php -r "echo bin2hex(random_bytes(32));"`

#### Variable 4 : REVERB_APP_SECRET
- **Name :** `REVERB_APP_SECRET`
- **Value :** `GÉNÉREZ_VOTRE_PROPRE_SECRET_ICI`
- **Note :** Générez avec : `php -r "echo bin2hex(random_bytes(32));"`

#### Variable 5 : REVERB_HOST
- **Name :** `REVERB_HOST`
- **Value :** `votre-app-backend.onrender.com`
- **Note :** Remplacez `votre-app-backend` par le nom réel de votre app Render

#### Variable 6 : REVERB_PORT
- **Name :** `REVERB_PORT`
- **Value :** `443`

#### Variable 7 : REVERB_SCHEME
- **Name :** `REVERB_SCHEME`
- **Value :** `https`

### Étape 3 : Modifier le Start Command

Allez dans : **Dashboard > Votre Service > Settings**

Cherchez **"Start Command"** et remplacez par :

```
cd backend && php artisan reverb:start --host=0.0.0.0 --port=8080 & php artisan serve --host=0.0.0.0 --port=8000
```

### Étape 4 : Redémarrer le service

Cliquez sur **"Manual Deploy"** > **"Clear build cache & deploy"**

Ou simplement sur **"Restart Service"** dans le dashboard.

### Étape 5 : Vérifier les logs

Allez dans **Logs** et vérifiez que vous voyez :
- `Reverb server started`
- `Laravel development server started`

---

## 📦 PARTIE 2 : VERCEL (Frontend)

### Étape 1 : Connectez-vous à Vercel

1. Allez sur [vercel.com](https://vercel.com)
2. Connectez-vous avec votre compte
3. Ouvrez votre projet U-Map Frontend

### Étape 2 : Ajouter les variables d'environnement

Allez dans : **Dashboard > Settings > Environment Variables**

Cliquez sur **"Add New"** pour chaque variable ci-dessous :

#### Variable 1 : VITE_API_URL (déjà existante - vérifiez-la)
- **Name :** `VITE_API_URL`
- **Value :** `https://votre-app-backend.onrender.com/api`
- **Note :** Remplacez `votre-app-backend` par le nom réel de votre app Render

#### Variable 2 : VITE_REVERB_APP_KEY
- **Name :** `VITE_REVERB_APP_KEY`
- **Value :** `MÊME_VALEUR_QUE_REVERB_APP_KEY_SUR_RENDER`
- **Note :** **IMPORTANT** : Mettez la MÊME valeur que REVERB_APP_KEY sur Render

#### Variable 3 : VITE_REVERB_HOST
- **Name :** `VITE_REVERB_HOST`
- **Value :** `votre-app-backend.onrender.com`
- **Note :** Remplacez `votre-app-backend` par le nom réel de votre app Render

#### Variable 4 : VITE_REVERB_PORT
- **Name :** `VITE_REVERB_PORT`
- **Value :** `443`

#### Variable 5 : VITE_REVERB_SCHEME
- **Name :** `VITE_REVERB_SCHEME`
- **Value :** `https`

### Étape 3 : Redéployer

Vercel se déploiera automatiquement depuis GitHub.

Si vous voulez forcer le déploiement :
- Allez dans **Deployments**
- Cliquez sur les trois points (...) à côté du dernier déploiement
- Cliquez sur **"Redeploy"**

### Étape 4 : Vérifier le build

Allez dans **Deployments** et vérifiez que le build est ✅ **Ready**

---

## ✅ CHECKLIST FINALE

### Render (Backend)
- [ ] Variable `BROADCAST_CONNECTION` = `reverb`
- [ ] Variable `REVERB_APP_ID` = `umap-reverb`
- [ ] Variable `REVERB_APP_KEY` = votre clé secrète
- [ ] Variable `REVERB_APP_SECRET` = votre secret
- [ ] Variable `REVERB_HOST` = votre domaine Render
- [ ] Variable `REVERB_PORT` = `443`
- [ ] Variable `REVERB_SCHEME` = `https`
- [ ] Start Command modifié pour démarrer Reverb
- [ ] Service redémarré
- [ ] Logs montrent Reverb démarré

### Vercel (Frontend)
- [ ] Variable `VITE_API_URL` = votre URL backend
- [ ] Variable `VITE_REVERB_APP_KEY` = MÊME que Render
- [ ] Variable `VITE_REVERB_HOST` = votre domaine Render
- [ ] Variable `VITE_REVERB_PORT` = `443`
- [ ] Variable `VITE_REVERB_SCHEME` = `https`
- [ ] Build réussi
- [ ] Déploiement Ready

---

## 🧪 TESTS APRÈS DÉPLOIEMENT

Une fois tout configuré, testez :

1. **Ouvrez votre site Vercel**
2. **Connectez-vous**
3. **Allez dans le Chat**
4. **Envoyez un message**
5. **Ouvrez le même chat sur un autre navigateur**
6. **Vérifiez que le message apparaît en temps réel**

Si ça fonctionne, tout est bon ! 🎉

---

## ❓ PROBLÈMES COURANTS

### "Reverb connection failed"
- Vérifiez que `VITE_REVERB_APP_KEY` est IDENTIQUE sur Render et Vercel
- Vérifiez que `VITE_REVERB_HOST` est correct (sans http://)

### "Build failed on Render"
- Vérifiez que le Start Command est correct
- Regardez les logs Render pour l'erreur exacte

### "Build failed on Vercel"
- Vérifiez que toutes les variables VITE_ sont ajoutées
- Regardez les logs Vercel pour l'erreur exacte

---

## 📞 BESOIN D'AIDE ?

Si vous avez des problèmes :
1. Regardez les logs Render et Vercel
2. Vérifiez que toutes les variables sont correctement copiées
3. Assurez-vous que les clés sont identiques entre Render et Vercel

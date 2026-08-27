<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-12 px-4">
    <div class="max-w-4xl mx-auto">
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-6">Politique de Confidentialité</h1>
        
        <div class="prose dark:prose-invert max-w-none">
          <p class="text-gray-600 dark:text-gray-300 mb-6">
            Dernière mise à jour : {{ currentDate }}
          </p>

          <h2 class="text-xl font-semibold text-gray-900 dark:text-white mt-6 mb-3">1. Introduction</h2>
          <p class="text-gray-600 dark:text-gray-300 mb-4">
            U-Map UAC ("nous", "notre" ou "l'Application") s'engage à protéger la vie privée de ses utilisateurs. 
            Cette politique de confidentialité explique comment nous collectons, utilisons et protégeons vos informations personnelles 
            lorsque vous utilisez notre application.
          </p>

          <h2 class="text-xl font-semibold text-gray-900 dark:text-white mt-6 mb-3">2. Informations que nous collectons</h2>
          
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mt-4 mb-2">2.1 Informations personnelles</h3>
          <ul class="list-disc pl-6 text-gray-600 dark:text-gray-300 mb-4">
            <li>Nom et prénom</li>
            <li>Adresse email</li>
            <li>Mot de passe (crypté)</li>
            <li>Statut d'étude et lieu d'étude (optionnel)</li>
            <li>Informations de localisation (avec votre consentement)</li>
          </ul>

          <h3 class="text-lg font-medium text-gray-900 dark:text-white mt-4 mb-2">2.2 Informations techniques</h3>
          <ul class="list-disc pl-6 text-gray-600 dark:text-gray-300 mb-4">
            <li>Adresse IP</li>
            <li>Type d'appareil et navigateur</li>
            <li>Données d'utilisation de l'application</li>
          </ul>

          <h2 class="text-xl font-semibold text-gray-900 dark:text-white mt-6 mb-3">3. Utilisation des informations</h2>
          <p class="text-gray-600 dark:text-gray-300 mb-4">
            Nous utilisons vos informations pour :
          </p>
          <ul class="list-disc pl-6 text-gray-600 dark:text-gray-300 mb-4">
            <li>Fournir et améliorer nos services</li>
            <li>Authentifier les utilisateurs</li>
            <li>Permettre la communication entre utilisateurs</li>
            <li>Envoyer des notifications importantes</li>
            <li>Analyser l'utilisation pour améliorer l'expérience utilisateur</li>
          </ul>

          <h2 class="text-xl font-semibold text-gray-900 dark:text-white mt-6 mb-3">4. Partage des informations</h2>
          <p class="text-gray-600 dark:text-gray-300 mb-4">
            Nous ne vendons pas vos informations personnelles. Nous pouvons partager vos informations uniquement dans les cas suivants :
          </p>
          <ul class="list-disc pl-6 text-gray-600 dark:text-gray-300 mb-4">
            <li>Avec votre consentement explicite</li>
            <li>Pour se conformer aux obligations légales</li>
            <li>Pour protéger nos droits et propriétés</li>
            <li>Avec des prestataires de services tiers (hébergement, email, etc.)</li>
          </ul>

          <h2 class="text-xl font-semibold text-gray-900 dark:text-white mt-6 mb-3">5. Sécurité des données</h2>
          <p class="text-gray-600 dark:text-gray-300 mb-4">
            Nous mettons en œuvre des mesures de sécurité appropriées pour protéger vos informations personnelles :
          </p>
          <ul class="list-disc pl-6 text-gray-600 dark:text-gray-300 mb-4">
            <li>Chiffrement des mots de passe</li>
            <li>Connexions sécurisées (HTTPS)</li>
            <li>Authentification à deux facteurs (2FA)</li>
            <li>Contrôle d'accès strict aux données</li>
          </ul>

          <h2 class="text-xl font-semibold text-gray-900 dark:text-white mt-6 mb-3">6. Vos droits</h2>
          <p class="text-gray-600 dark:text-gray-300 mb-4">
            Conformément au RGPD, vous avez les droits suivants :
          </p>
          <ul class="list-disc pl-6 text-gray-600 dark:text-gray-300 mb-4">
            <li>Accéder à vos données personnelles</li>
            <li>Rectifier vos données inexactes</li>
            <li>Supprimer vos données (droit à l'oubli)</li>
            <li>Limiter le traitement de vos données</li>
            <li>Exporter vos données</li>
            <li>Retirer votre consentement</li>
          </ul>

          <h2 class="text-xl font-semibold text-gray-900 dark:text-white mt-6 mb-3">7. Conservation des données</h2>
          <p class="text-gray-600 dark:text-gray-300 mb-4">
            Nous conservons vos données personnelles aussi longtemps que nécessaire pour fournir nos services 
            et conformément aux exigences légales. Les messages sont automatiquement supprimés après 7 jours.
          </p>

          <h2 class="text-xl font-semibold text-gray-900 dark:text-white mt-6 mb-3">8. Cookies</h2>
          <p class="text-gray-600 dark:text-gray-300 mb-4">
            Nous utilisons des cookies pour améliorer votre expérience utilisateur. Vous pouvez configurer votre navigateur 
            pour refuser les cookies, mais cela peut affecter certaines fonctionnalités de l'application.
          </p>

          <h2 class="text-xl font-semibold text-gray-900 dark:text-white mt-6 mb-3">9. Modifications de cette politique</h2>
          <p class="text-gray-600 dark:text-gray-300 mb-4">
            Nous nous réservons le droit de modifier cette politique de confidentialité. Les modifications seront 
            publiées sur cette page avec la date de mise à jour.
          </p>

          <h2 class="text-xl font-semibold text-gray-900 dark:text-white mt-6 mb-3">10. Contact</h2>
          <p class="text-gray-600 dark:text-gray-300 mb-4">
            Pour toute question concernant cette politique de confidentialité ou vos données personnelles, 
            veuillez nous contacter à : <a href="mailto:contact@umap-uac.bj" class="text-blue-600 hover:underline">contact@umap-uac.bj</a>
          </p>
        </div>

        <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
          <router-link to="/" class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
            ← Retour à l'accueil
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const currentDate = ref('')

onMounted(() => {
  const today = new Date()
  currentDate.value = today.toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
})
</script>

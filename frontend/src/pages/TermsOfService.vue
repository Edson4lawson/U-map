<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-12 px-4">
    <div class="max-w-4xl mx-auto">
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-6">Conditions d'Utilisation</h1>
        
        <div class="prose dark:prose-invert max-w-none">
          <p class="text-gray-600 dark:text-gray-300 mb-6">
            Dernière mise à jour : {{ currentDate }}
          </p>

          <h2 class="text-xl font-semibold text-gray-900 dark:text-white mt-6 mb-3">1. Acceptation des conditions</h2>
          <p class="text-gray-600 dark:text-gray-300 mb-4">
            En utilisant l'application U-Map UAC, vous acceptez les présentes conditions d'utilisation. 
            Si vous n'acceptez pas ces conditions, veuillez ne pas utiliser notre application.
          </p>

          <h2 class="text-xl font-semibold text-gray-900 dark:text-white mt-6 mb-3">2. Description du service</h2>
          <p class="text-gray-600 dark:text-gray-300 mb-4">
            U-Map UAC est une application interactive qui permet aux étudiants de l'Université d'Abomey-Calavi (UAC) de :
          </p>
          <ul class="list-disc pl-6 text-gray-600 dark:text-gray-300 mb-4">
            <li>Visualiser la carte du campus avec les lieux importants</li>
            <li>Ajouter et partager des lieux personnalisés</li>
            <li>Signaler des problèmes en temps réel</li>
            <li>Communiquer avec d'autres étudiants via la messagerie</li>
            <li>Trouver des camarades d'étude</li>
          </ul>

          <h2 class="text-xl font-semibold text-gray-900 dark:text-white mt-6 mb-3">3. Compte utilisateur</h2>
          
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mt-4 mb-2">3.1 Inscription</h3>
          <p class="text-gray-600 dark:text-gray-300 mb-4">
            Pour utiliser certaines fonctionnalités, vous devez créer un compte en fournissant des informations exactes et complètes. 
            Vous êtes responsable de la confidentialité de vos identifiants de connexion.
          </p>

          <h3 class="text-lg font-medium text-gray-900 dark:text-white mt-4 mb-2">3.2 Sécurité du compte</h3>
          <p class="text-gray-600 dark:text-gray-300 mb-4">
            Vous devez notifier immédiatement toute utilisation non autorisée de votre compte. 
            Nous ne serons pas responsables des pertes résultant d'une utilisation non autorisée de votre compte.
          </p>

          <h2 class="text-xl font-semibold text-gray-900 dark:text-white mt-6 mb-3">4. Comportement de l'utilisateur</h2>
          <p class="text-gray-600 dark:text-gray-300 mb-4">
            En utilisant U-Map UAC, vous vous engagez à :
          </p>
          <ul class="list-disc pl-6 text-gray-600 dark:text-gray-300 mb-4">
            <li>Ne pas publier de contenu offensant, discriminatoire ou illégal</li>
            <li>Ne pas harceler, menacer ou intimider d'autres utilisateurs</li>
            <li>Ne pas partager de fausses informations</li>
            <li>Respecter la vie privée des autres utilisateurs</li>
            <li>Ne pas utiliser l'application à des fins commerciales sans autorisation</li>
          </ul>

          <h2 class="text-xl font-semibold text-gray-900 dark:text-white mt-6 mb-3">5. Contenu utilisateur</h2>
          
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mt-4 mb-2">5.1 Propriété</h3>
          <p class="text-gray-600 dark:text-gray-300 mb-4">
            Vous conservez la propriété du contenu que vous publiez. En publiant du contenu, vous nous accordez 
            une licence mondiale, non exclusive et gratuite pour utiliser, afficher et distribuer ce contenu dans le cadre de nos services.
          </p>

          <h3 class="text-lg font-medium text-gray-900 dark:text-white mt-4 mb-2">5.2 Modération</h3>
          <p class="text-gray-600 dark:text-gray-300 mb-4">
            Nous nous réservons le droit de modérer, supprimer ou restreindre tout contenu qui viole ces conditions d'utilisation.
          </p>

          <h2 class="text-xl font-semibold text-gray-900 dark:text-white mt-6 mb-3">6. Confidentialité</h2>
          <p class="text-gray-600 dark:text-gray-300 mb-4">
            Votre utilisation de U-Map UAC est également régie par notre Politique de Confidentialité. 
            Veuillez consulter notre politique de confidentialité pour comprendre comment nous collectons, utilisons et protégeons vos données.
          </p>

          <h2 class="text-xl font-semibold text-gray-900 dark:text-white mt-6 mb-3">7. Propriété intellectuelle</h2>
          <p class="text-gray-600 dark:text-gray-300 mb-4">
            U-Map UAC et son contenu original, ses fonctionnalités et sa conception sont la propriété exclusive de U-Map UAC 
            et sont protégés par les lois sur la propriété intellectuelle.
          </p>

          <h2 class="text-xl font-semibold text-gray-900 dark:text-white mt-6 mb-3">8. Limitation de responsabilité</h2>
          <p class="text-gray-600 dark:text-gray-300 mb-4">
            Dans la mesure maximale permise par la loi, U-Map UAC ne sera pas responsable des dommages directs, indirects, 
            accessoires, spéciaux ou consécutifs résultant de l'utilisation ou de l'impossibilité d'utiliser nos services.
          </p>

          <h2 class="text-xl font-semibold text-gray-900 dark:text-white mt-6 mb-3">9. Indemnisation</h2>
          <p class="text-gray-600 dark:text-gray-300 mb-4">
            Vous acceptez d'indemniser et de dégager U-Map UAC de toute réclamation, responsabilité, perte, 
            dommage ou dépense résultant de votre violation de ces conditions d'utilisation.
          </p>

          <h2 class="text-xl font-semibold text-gray-900 dark:text-white mt-6 mb-3">10. Résiliation</h2>
          <p class="text-gray-600 dark:text-gray-300 mb-4">
            Nous nous réservons le droit de résilier ou suspendre votre compte à tout moment, sans préavis, 
            pour violation de ces conditions d'utilisation ou pour toute autre raison que nous jugerons appropriée.
          </p>

          <h2 class="text-xl font-semibold text-gray-900 dark:text-white mt-6 mb-3">11. Modifications des conditions</h2>
          <p class="text-gray-600 dark:text-gray-300 mb-4">
            Nous nous réservons le droit de modifier ces conditions d'utilisation à tout moment. 
            Les modifications entreront en vigueur dès leur publication sur cette page.
          </p>

          <h2 class="text-xl font-semibold text-gray-900 dark:text-white mt-6 mb-3">12. Loi applicable</h2>
          <p class="text-gray-600 dark:text-gray-300 mb-4">
            Ces conditions d'utilisation sont régies par les lois du Bénin. Tout litige sera soumis aux tribunaux compétents du Bénin.
          </p>

          <h2 class="text-xl font-semibold text-gray-900 dark:text-white mt-6 mb-3">13. Contact</h2>
          <p class="text-gray-600 dark:text-gray-300 mb-4">
            Pour toute question concernant ces conditions d'utilisation, veuillez nous contacter à : 
            <a href="mailto:contact@umap-uac.bj" class="text-blue-600 hover:underline">contact@umap-uac.bj</a>
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

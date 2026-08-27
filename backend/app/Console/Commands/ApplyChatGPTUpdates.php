<?php

namespace App\Console\Commands;

use App\Models\Place;
use Illuminate\Console\Command;

class ApplyChatGPTUpdates extends Command
{
    protected $signature = 'campus:apply-chatgpt-updates
                            {--commit : Apply changes to database (dry-run by default)}';

    protected $description = 'Apply ChatGPT-provided category and description updates to places';

    // Mapping des mises à jour ChatGPT corrigées (ID => [category, description])
    private $updates = [
        343 => ['office', "Bâtiment administratif principal de l'École Polytechnique d'Abomey-Calavi (EPAC), où sont assurées les démarches administratives, académiques et la gestion des étudiants."],
        307 => ['office', "Services administratifs de la Faculté des Sciences et Techniques (FAST), chargés de la gestion des dossiers académiques et administratifs."],
        341 => ['office', "Bâtiment administratif de la Faculté de Droit et de Sciences Politiques, accueillant les services destinés aux étudiants et au personnel."],
        53 => ['office', "Administration centrale de la Faculté des Sciences Économiques et de Gestion, responsable des formalités administratives et académiques."],
        309 => ['amphitheatre', "Grand amphithéâtre d'une capacité d'environ 1000 places, utilisé pour les cours magistraux, conférences et événements universitaires."],
        339 => ['amphitheatre', "Amphithéâtre d'environ 150 places destiné aux enseignements, séminaires et conférences de taille moyenne."],
        340 => ['amphitheatre', "Amphithéâtre de 400 places accueillant les cours magistraux, soutenances et manifestations académiques."],
        317 => ['amphitheatre', "Amphithéâtre de 500 places utilisé pour les cours magistraux et les rencontres académiques."],
        310 => ['amphitheatre', "Amphithéâtre dédié aux cours magistraux, conférences, séminaires et événements scientifiques de l'université."],
        319 => ['amphitheatre', "Grand amphithéâtre d'environ 1000 places destiné aux grands rassemblements universitaires et aux enseignements magistraux."],
        318 => ['amphitheatre', "Amphithéâtre de 500 places accueillant les enseignements, conférences et activités académiques."],
        308 => ['amphitheatre', "Amphithéâtre de 750 places utilisé pour les cours magistraux et les conférences universitaires."],
        326 => ['amphitheatre', "Amphithéâtre de l'EPAC destiné aux enseignements, conférences et soutenances académiques."],
        311 => ['amphitheatre', "Grand amphithéâtre de 1000 places utilisé pour les cours magistraux, conférences et cérémonies universitaires."],
        320 => ['amphitheatre', "Amphithéâtre accueillant les cours magistraux, conférences et rencontres scientifiques."],
        312 => ['amphitheatre', "Amphithéâtre utilisé pour les enseignements universitaires, conférences et soutenances."],
        327 => ['amphitheatre', "Amphithéâtre de l'IFRI servant aux cours magistraux, conférences et activités académiques de l'institut."],
        313 => ['amphitheatre', "Amphithéâtre destiné aux enseignements, conférences et manifestations académiques de l'UAC."],
        111 => ['amphitheatre', "Amphithéâtre du MIRD utilisé pour les cours, conférences et activités scientifiques."],
        321 => ['amphitheatre', "Amphithéâtre accueillant les cours magistraux, conférences et événements académiques."],
        314 => ['amphitheatre', "Amphithéâtre universitaire dédié aux enseignements, conférences et rencontres scientifiques."],
        304 => ['amphitheatre', "Amphithéâtre utilisé pour les cours magistraux, conférences et événements académiques."],
        274 => ['amphitheatre', "Amphithéâtre destiné aux enseignements, conférences et diverses activités universitaires."],
        359 => ['amphitheatre', "Amphithéâtre de l'Institut du Cadre de Vie (ICaV), utilisé pour les enseignements et les conférences."],
        48 => ['library', "Bibliothèque principale de l'Université d'Abomey-Calavi, offrant des ouvrages, mémoires, thèses, ressources documentaires et espaces de travail."],
        328 => ['library', "Centre documentaire mettant à disposition des étudiants et chercheurs des ressources pédagogiques et scientifiques."],
        58 => ['library', "Bibliothèque de l'EPAC proposant des ouvrages spécialisés, des ressources scientifiques et des espaces d'étude."],
        59 => ['library', "Bibliothèque universitaire de la FADESP offrant des ressources documentaires adaptées aux différentes filières de la faculté."],
        322 => ['library', "Bibliothèque universitaire mettant à disposition des étudiants et chercheurs des ouvrages, espaces de lecture et ressources académiques."],
        342 => ['laboratory', "Bâtiment regroupant plusieurs laboratoires destinés aux travaux pratiques, aux expérimentations et aux activités de recherche scientifique."],
        344 => ['building', "Bâtiment de l'EPAC accueillant des salles de cours, des bureaux administratifs et des espaces dédiés aux activités pédagogiques."],
        345 => ['building', "Bâtiment de la Faculté des Sciences Agronomiques regroupant des salles d'enseignement, des laboratoires et des bureaux."],
        346 => ['building', "Bâtiment universitaire de l'EPAC dédié aux enseignements, aux travaux pratiques et aux activités académiques."],
        347 => ['building', "Bâtiment de la Faculté des Sciences Agronomiques accueillant des salles de cours et des espaces de recherche."],
        348 => ['building', "Bâtiment de l'EPAC regroupant des salles d'enseignement, des laboratoires et des bureaux administratifs."],
        349 => ['building', "Bâtiment universitaire de l'EPAC consacré aux activités pédagogiques, scientifiques et administratives."],
        303 => ['building', "Bâtiment de la Faculté des Sciences Agronomiques destiné aux enseignements et à la recherche."],
        329 => ['building', "Bâtiment de l'EPAC accueillant des salles de cours, des laboratoires et des bureaux universitaires."],
        323 => ['building', "Bâtiment de la Faculté des Sciences Agronomiques utilisé pour les enseignements, la recherche et les services universitaires."],
        110 => ['building', "Bâtiment principal du MIRD regroupant les espaces d'enseignement, de recherche et les services administratifs."],
        251 => ['research_center', "Centre de recherche universitaire spécialisé dans les mathématiques et leurs applications, reconnu comme Chaire UNESCO."],
        358 => ['office', "Bâtiment administratif regroupant le décanat et les services de gestion de la FLLAC et de la FASHS."],
        330 => ['department', "Département académique de l'EPAC chargé de la formation, de la recherche et des travaux pratiques en génie civil."],
        315 => ['department', "Département universitaire assurant l'enseignement, la recherche et la promotion de la langue et de la culture espagnoles."],
        361 => ['department', "Département chargé de l'enseignement de la langue allemande, de la littérature et des cultures germaniques."],
        350 => ['department', "Département spécialisé dans la formation et la recherche en génétique, biotechnologie et sciences du vivant."],
        331 => ['campus', "Ensemble de bâtiments et d'infrastructures de l'EPAC regroupant les espaces pédagogiques, administratifs et techniques."],
        352 => ['faculty', "Faculté des Sciences et Techniques de l'Université d'Abomey-Calavi, regroupant plusieurs départements, laboratoires et infrastructures d'enseignement."],
        50 => ['faculty', "Faculté spécialisée dans la formation et la recherche en droit, sciences politiques et disciplines juridiques."],
        51 => ['faculty', "Faculté proposant des formations en lettres, langues, arts, communication et sciences humaines."],
        52 => ['faculty', "Faculté spécialisée dans les sciences agronomiques, l'agriculture, l'élevage, l'environnement et le développement rural."],
        299 => ['farm', "Ferme pédagogique et expérimentale utilisée pour les travaux pratiques, la recherche et les activités de production agricole."],
        300 => ['print_shop', "Service d'impression et de reprographie destiné aux étudiants, enseignants et personnels administratifs de l'université."],
        56 => ['institute', "Institut dédié à l'enseignement de la langue chinoise ainsi qu'à la promotion de la culture chinoise au sein de l'UAC."],
        57 => ['institute', "Institut de formation et de recherche spécialisé dans les ressources en eau, l'hydrologie et la gestion environnementale."],
        93 => ['institute', "Institut spécialisé dans les formations en informatique, intelligence artificielle, cybersécurité, réseaux et technologies numériques."],
        94 => ['institute', "Centre universitaire proposant des formations à distance et des ressources numériques pour l'enseignement supérieur."],
        281 => ['university', "Plus grande université publique du Bénin, regroupant plusieurs facultés, écoles, instituts, laboratoires et infrastructures de recherche."],
        22 => ['academic_area', "Zone universitaire regroupant les salles de cours, laboratoires et espaces destinés aux formations de Master et à la recherche."],
        49 => ['school', "Établissement public de formation spécialisé dans l'administration publique, la magistrature et les carrières juridiques."],
        92 => ['school', "Grande école d'ingénieurs de l'UAC offrant des formations en génie, technologies, architecture et sciences appliquées."],
        351 => ['institute', "École doctorale chargée de la formation des doctorants, de l'encadrement de la recherche et de la coordination des études doctorales au sein de la FADESP."],
        55 => ['bank', "Agence bancaire offrant des services financiers, des opérations bancaires et des solutions de paiement à la communauté universitaire."],
        54 => ['atm', "Distributeur automatique permettant les retraits d'espèces et certaines opérations bancaires 24h/24."],
        316 => ['laboratory', "Laboratoire de la Faculté des Sciences et Techniques destiné aux travaux pratiques, aux expérimentations et aux activités de recherche scientifique."],
        356 => ['laboratory', "Laboratoire universitaire polyvalent utilisé pour les travaux pratiques, les expérimentations et les projets de recherche."],
        272 => ['laboratory', "Laboratoire de la FAST dédié aux activités pédagogiques et scientifiques."],
        353 => ['laboratory', "Laboratoire spécialisé dans les analyses biologiques, les expérimentations et les recherches en sciences du vivant."],
        332 => ['laboratory', "Laboratoire spécialisé dans l'étude des ressources en eau, des ouvrages hydrauliques et de la gestion de l'eau."],
        278 => ['laboratory', "Centre de recherche consacré à l'écologie, à la biodiversité et à la gestion durable des écosystèmes."],
        324 => ['laboratory', "Laboratoire dédié à l'étude des insectes, de la lutte biologique et des recherches en entomologie appliquée."],
        333 => ['laboratory', "Laboratoire spécialisé dans les écosystèmes aquatiques, l'aquaculture et la gestion des ressources halieutiques."],
        338 => ['laboratory', "Centre de recherche consacré aux zones humides, à l'hydrobiologie et à la conservation des milieux aquatiques."],
        325 => ['laboratory', "Laboratoire spécialisé dans l'analyse des ressources hydriques, des bassins versants et des phénomènes hydrologiques."],
        334 => ['laboratory', "Laboratoire consacré aux recherches en biotechnologie animale, amélioration génétique et reproduction."],
        273 => ['laboratory', "Laboratoire spécialisé en cartographie, systèmes d'information géographique (SIG) et analyse spatiale."],
        354 => ['laboratory', "Laboratoire étudiant les micro-organismes des sols et leurs interactions avec l'environnement."],
        335 => ['laboratory', "Centre de recherche spécialisé dans les maladies animales, la microbiologie et l'immunologie vétérinaire."],
        336 => ['laboratory', "Laboratoire consacré à l'étude du fonctionnement des organismes vivants et aux expérimentations en physiologie."],
        337 => ['laboratory', "Laboratoire de recherche développant des travaux en biologie appliquée et en sciences du vivant."],
        301 => ['laboratory', "Laboratoire spécialisé dans l'étude de la répartition géographique des espèces animales et de leur environnement."],
        355 => ['laboratory', "Laboratoire de recherche en génétique moléculaire, biotechnologie et amélioration des organismes vivants."],
        279 => ['laboratory', "Laboratoire dédié aux expérimentations et aux recherches dans le domaine de l'hydraulique."],
        280 => ['laboratory', "Centre de recherche spécialisé dans les sciences de l'eau, l'environnement et le développement durable."],
        239 => ['restaurant', "Espace de restauration proposant des repas rapides, boissons et collations aux étudiants et au personnel."],
        298 => ['restaurant', "Restaurant universitaire offrant des repas à prix subventionnés pour les étudiants et le personnel."],
        302 => ['restaurant', "Restaurant universitaire proposant des repas équilibrés dans un cadre adapté à la communauté universitaire."],
        305 => ['restaurant', "Restaurant universitaire assurant un service de restauration pour les étudiants, enseignants et visiteurs."],
        233 => ['cafe', "Café-restaurant proposant boissons, repas légers et espace de détente au sein du campus."],
        306 => ['research_center', "Salle de travail réservée aux doctorants de l'IFRI pour les activités de recherche, de collaboration et de rédaction scientifique."],
        357 => ['botanical_garden', "Jardin scientifique consacré à la conservation de la biodiversité, à l'enseignement et à la recherche en botanique et zoologie."],
        360 => ['greenhouse', "Serre expérimentale utilisée pour les recherches en génétique végétale, biotechnologie et cultures expérimentales."],
        68 => ['fuel', "Station-service située à proximité du campus, offrant carburants et services automobiles."],
        127 => ['fuel', "Station-service proposant la distribution de carburants et divers services aux usagers du campus."],
        271 => ['administration', "Siège administratif principal de l'Université d'Abomey-Calavi, regroupant les services de gouvernance et la présidence de l'université."],
        270 => ['administration', "Bâtiment administratif complémentaire accueillant certains services du rectorat."],
        276 => ['administration', "Bâtiment administratif hébergeant les services du Vice-Rectorat et les directions universitaires."],
        275 => ['health_center', "Service médical de l'Université d'Abomey-Calavi assurant les soins de santé de première nécessité aux étudiants et au personnel."],
        292 => ['dormitory', "Résidence universitaire destinée au logement des étudiants de l'Université d'Abomey-Calavi."],
        282 => ['dormitory', "Résidence universitaire offrant des logements aux étudiants dans un cadre propice aux études."],
        293 => ['dormitory', "Résidence universitaire réservée à l'hébergement des étudiants."],
        283 => ['dormitory', "Résidence universitaire proposant des chambres pour les étudiants de l'UAC."],
        291 => ['dormitory', "Résidence universitaire accueillant principalement des étudiants sur le campus."],
        284 => ['dormitory', "Résidence universitaire destinée à l'hébergement des étudiants."],
        285 => ['dormitory', "Résidence universitaire située sur le campus de l'UAC."],
        286 => ['dormitory', "Résidence universitaire offrant des logements aux étudiants."],
        287 => ['dormitory', "Résidence universitaire réservée aux étudiants de l'université."],
        288 => ['dormitory', "Résidence universitaire offrant un hébergement à proximité des facultés."],
        294 => ['dormitory', "Résidence universitaire destinée aux étudiants inscrits à l'UAC."],
        289 => ['dormitory', "Résidence universitaire située au sein du campus universitaire."],
        295 => ['dormitory', "Résidence universitaire accueillant les étudiants pendant leur parcours académique."],
        296 => ['dormitory', "Résidence universitaire proposant des logements sur le campus."],
        290 => ['dormitory', "Résidence universitaire offrant un cadre de vie adapté aux étudiants."],
        297 => ['dormitory', "Résidence universitaire destinée à l'hébergement des étudiants et chercheurs."],
        115 => ['amphitheatre', "Amphithéâtre universitaire utilisé pour les cours magistraux, conférences et événements académiques."],
    ];

    public function handle(): int
    {
        $commit = $this->option('commit');

        $this->info('=== Application des mises à jour ChatGPT (catégorie + description) ===');
        $this->info($commit ? '🔴 MODE COMMIT — les modifications seront écrites en base.' : '🟢 MODE DRY-RUN — aucune écriture. Utilisez --commit pour appliquer.');
        $this->newLine();

        $updated = 0;
        $notFound = 0;
        $sample = [];

        foreach ($this->updates as $id => $update) {
            $place = Place::find($id);

            if (!$place) {
                $this->warn("  ⚠️  Lieu ID {$id} non trouvé en base");
                $notFound++;
                continue;
            }

            $newCategory = $update[0];
            $newDescription = $update[1];

            // Vérifier si des changements sont nécessaires
            if ($place->category === $newCategory && $place->description === $newDescription) {
                continue;
            }

            $this->info("  ✓ [{$place->id}] {$place->name}");
            $this->line("    Catégorie : {$place->category} → {$newCategory}");
            $this->line("    Description : {$place->description}");
            $this->line("    → {$newDescription}");
            $this->newLine();

            if ($commit) {
                $place->category = $newCategory;
                $place->description = $newDescription;
                $place->save();
            }

            $updated++;

            // Collecter un échantillon de 10 lieux
            if (count($sample) < 10) {
                $sample[] = [
                    'id' => $place->id,
                    'name' => $place->name,
                    'old_category' => $place->category,
                    'new_category' => $newCategory,
                    'old_description' => $place->description,
                    'new_description' => $newDescription,
                ];
            }
        }

        $this->newLine();
        $this->info('=== Résumé ===');
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Lieux mis à jour', $updated],
                ['Lieux non trouvés', $notFound],
                ['Total mises à jour prévues', count($this->updates)],
                ['Mode', $commit ? 'COMMIT' : 'DRY-RUN'],
            ]
        );

        if (!$commit && $updated > 0) {
            $this->newLine();
            $this->warn('💡 Relancez avec --commit pour appliquer les modifications en base.');
        }

        // Afficher l'échantillon de 10 lieux
        if (!empty($sample)) {
            $this->newLine();
            $this->info('=== Échantillon de 10 lieux (avant/après) ===');
            foreach ($sample as $i => $item) {
                $this->line("[" . ($i + 1) . "] ID {$item['id']} - {$item['name']}");
                $this->line("    Catégorie : {$item['old_category']} → {$item['new_category']}");
                $this->line("    Description : {$item['old_description']}");
                $this->line("    → {$item['new_description']}");
                $this->newLine();
            }
        }

        return self::SUCCESS;
    }
}

// classifyPlaces.js — classement des lieux U-map en filtres thématiques
//
// 1. Mots entiers / préfixes de mots (pas de collisions de sous-chaînes)
// 2. Recherche uniquement dans NOM + CATEGORY/TYPE
// 3. Classement EXCLUSIF avec ordre de priorité : 1 lieu = 1 seul filtre
// 4. Gestion des caractères spéciaux (œ, æ, diacritiques)

export const norm = (s) =>
  String(s ?? '')
    .toLowerCase()
    .replace(/œ/g, 'oe')
    .replace(/æ/g, 'ae')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[’']/g, ' ');

// "mot" = mot entier ; "mot*" = préfixe de mot (amphi* → amphi, amphitheatre…)
const re = (kws) =>
  new RegExp(
    '(?<![a-z0-9])(' +
      kws.map((k) => (k.endsWith('*') ? k.slice(0, -1) + '[a-z0-9]*' : k)).join('|') +
      ')(?![a-z0-9])'
  );

const has = (kws) => {
  const r = re(kws);
  return (name, text) => r.test(text);
};

// Noms qui COMMENCENT par un terme d'enseignement → facultés/enseignement,
// même s'ils contiennent "administration" (ex: École Nationale d'Administration…)
const STUDY_START =
  /^(ecole|faculte|institut|departement|laboratoire|labo\b|batiment|salle des doctorants|zone master|serre|bloc laboratoire|ferme|chaire|cipma|universite)/;

// Ordre = priorité (le premier qui matche gagne)
export const RULES = [
  ['health', has(['smel', 'sante', 'securite', 'infirmerie', 'medical*', 'medecin*', 'pharmacie*', 'clinique', 'hopital', 'chu', 'urgence*', 'gardiennage', 'soins'])],
  ['library', has(['bibliotheque*', 'biblio*', 'buac', 'documentation', 'library'])],
  ['food', has(['restaur*', 'resto*', 'ru', 'cantine*', 'cafe', 'cafeteria', 'buvette', 'maquis', 'snack'])],
  ['housing', has(['residence*', 'cite', 'dortoir*', 'logement*'])],
  ['amphi', has(['amphi*', 'auditorium'])],
  ['none', (name) => /^campus\b/.test(name)], // le campus lui-même : pas un lieu à filtrer
  ['studies', (name) => STUDY_START.test(name)],
  ['admin', has(['rectorat', 'decanat', 'administration*', 'administratif*', 'scolarite', 'gouvernance', 'secretariat'])],
  ['services', has(['ecobank', 'banque*', 'bank', 'gab', 'atm', 'station*', 'mrs', 'puma', 'imprimerie', 'poste', 'centre commercial', 'boutique*', 'kiosque*', 'reprographie'])],
  ['sport', has(['sport*', 'stade', 'stadium', 'terrain*', 'foot*', 'basket*', 'handball', 'volley*', 'gymnase*', 'piscine', 'athletisme', 'culture*', 'theatre', 'mosquee*', 'chapelle', 'eglise', 'culte', 'oecumenique', 'priere*', 'jardin*', 'loisir*', 'detente', 'village', 'maison des etudiants'])],
  // Filet de sécurité : sigles des entités d'enseignement (en dernier)
  ['studies', has(['faculte*', 'ecole*', 'institut*', 'departement*', 'laboratoire*', 'labo', 'recherche', 'enseignement', 'academique*', 'master', 'doctora*', 'fast', 'fss', 'faseg', 'fllac', 'fashs', 'fadesp', 'fsa', 'epac', 'ifri', 'eneam', 'enstic', 'imsp', 'iut', 'enam', 'uva', 'ine', 'icav', 'mird', 'lacarto'])],
];

export function classify(p) {
  const props = p.properties || p;
  const name = norm(props.name);
  const cat = norm([props.category, props.type].filter(Boolean).join(' '));
  const text = `${name} | ${cat}`;
  for (const [id, test] of RULES) {
    if (test(name, text)) return id === 'none' ? null : id;
  }
  return null;
}

// Même signature que matchFilter(p, filterId, isVisited)
export function matchFilter(p, filterId, isVisited = false) {
  if (filterId === 'all') return true;
  if (filterId === 'visited') return isVisited;
  return classify(p) === filterId;
}

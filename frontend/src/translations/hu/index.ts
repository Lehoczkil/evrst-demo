export default {
  manifesto: {
    eyebrow: 'Amiért ez az egész van',
    body: 'Az Escape Velocity Rocketry Student Team az Óbudai Egyetem hallgatói rakétacsapata. Kísérleti rakétákat tervezünk, építünk és indítunk, miközben a hallgatói mérnöki munka határait feszegetjük.',
    second: 'Kilenc szakmai csoport, egy próbapad és három rakéta. Egy már repült — a következő épp épül.',
    founded: 'Alapítva',
    members: 'Aktív tag',
    groups: 'Csoport',
    vehicles: 'Rakéta',
    mark1: 'CAD → próbapad → kilövés',
    mark2: 'EuRoC · Spaceport America Cup',
    mark3: 'Nyílt dokumentáció',
    goal1: {
      title: 'Mérnöki kiválóság',
      description: 'Hallgatók képzése valós űrtechnikai feladatokon.',
    },
    goal2: {
      title: 'Nemzetközi verseny',
      description: 'Az Óbudai Egyetem képviselete a nagy diákversenyeken.',
    },
    goal3: {
      title: 'Inspiráció',
      description: 'Munkánk megosztása és a hazai diák-űrközösség erősítése.',
    },
  },
  rocket: {
    eyebrow: 'Aktív jármű',
    title: 'A rakéta',
    status: 'Építés alatt',
    dimHeight: '2 400 mm',
    dimDiameter: '⌀ 102',
    blueprintAlt: 'A rakéta felépítése',
    subsystemHeading: 'Alrendszer → felelős csoport',
    /*
      Open question §14/2: these could equally be a CMS row so the team can
      edit them without a deploy. Here for now, because the labels have to
      be translated either way and splitting a label from its value across
      two systems is worse than either.
    */
    specs: [
      { label: 'Magasság', value: '2 400', unit: 'mm' },
      { label: 'Átmérő', value: '102', unit: 'mm' },
      { label: 'Felszálló massza', value: '11,4', unit: 'kg' },
      { label: 'Tolóerő', value: '1 320', unit: 'N' },
      { label: 'Cél csúcsmagasság', value: '3 000', unit: 'm' },
      { label: 'Hajtómű', value: 'K', unit: 'osztály' },
    ],
    subsystem: {
      avionics: 'Avionika',
      software: 'Fedélzeti szoftver',
      propulsion: 'Hajtómű',
      structures: 'Váz és aerodinamika',
    },
  },
  programme: {
    eyebrow: '{total} rakéta a programban',
    title: 'Amit építünk',
    empty: 'A programot még nem töltöttük fel — hamarosan itt lesz.',
    apogee1: 'Elért csúcsmagasság · 640 m',
    apogee2: 'Cél csúcsmagasság · 3 000 m',
    apogee3: 'EuRoC kategória · 9 000 m',
    state: {
      flown: 'Repült',
      building: 'Építés alatt',
      design: 'Tervezés',
    },
    vehicles: [
      {
        title: 'Atlas-1',
        description: 'Kis magasságú tesztplatform az avionika, a mentés és a hajtóművek validálására.',
      },
      {
        title: 'Helios',
        description: 'Közepes teljesítményű rakéta a fedélzeti számítógép és a kettős mentés tesztelésére.',
      },
      {
        title: 'Voyager',
        description: 'Versenyképes rakéta nemzetközi diákversenyekre.',
      },
    ],
  },
  events: {
    title: 'Események',
    next: 'Következő',
    eyebrowNone: 'Nincs meghirdetett esemény',
    upcoming: 'Közelgő',
    log: 'Napló',
    empty: 'Még nincs feltöltött esemény.',
    noUpcoming: 'Most nincs meghirdetett esemény — a naplóban látod, mi volt.',
    noPast: 'A napló még üres.',
  },
  team: {
    eyebrow: '{members} tag · {groups} csoport',
    title: 'A csapat',
    openPositions: 'Csatlakozz',
    empty: 'A csapat listája hamarosan.',
    other: 'Egyéb',
    mentors: '{count} mentor',
    group: {
      'csapat-menedzser': 'Csapat menedzser',
      'projekt-menedzser': 'Projekt menedzser',
      'marketing-dizajn': 'Marketing-Dizájn',
      elektronika: 'Elektronika',
      szoftver: 'Szoftver',
      hajtomu: 'Hajtómű',
      'vaz-aerodinamika': 'Váz-Aerodinamika',
      jog: 'Jog',
      webfejleszto: 'Webfejlesztő',
    },
  },
  sponsors: {
    eyebrow: '{count} támogató',
    title: 'Támogatók',
    pitchTitle: 'Egy hallgatói rakétacsapat nem építkezik magától',
    pitchBody: 'Anyag, gépidő, próbapad, utazás a versenyre. Cserébe a rakétán, a dokumentációban és minden megjelenésünkben ott a logója.',
    cta: 'Legyen támogató',
    mailSubject: 'Támogatás',
    empty: 'Első támogatónk helye — szívesen beszélünk róla.',
  },
  join: {
    eyebrow: 'Jelentkezés nyitva',
    title: 'Csatlakozz a csapathoz',
    lede: 'Nem kell mérnöknek lenned. A rakétához legalább annyira kell marketing, jog és webfejlesztés, mint hajtómű.',
    cta: 'Jelentkezem',
    question: 'Kérdésem van',
    disciplines: 'Szakmai csoportjaink',
  },
  footer: {
    site: 'Oldal',
    team: 'Csapat',
    contact: 'Kapcsolat',
    admin: 'Admin belépés',
  },
  contact: {
    address: 'Óbudai Egyetem · Bécsi út 96/b, 1034 Budapest',
  },
  state: {
    fetchFailed: 'Ezt a részt most nem sikerült betölteni.',
    retry: 'Újra',
    loading: 'Betöltés…',
  },
  notFound: {
    code: '404',
    title: 'Ez az oldal nincs meg',
    body: 'Lehet, hogy elírtuk a linket, vagy már nem létezik. A lap tetejéről minden elérhető.',
    home: 'Vissza a főoldalra',
  },
  form: {
    required: 'kötelező',
    submit: 'Jelentkezés elküldése',
    sending: 'Küldés…',
    successTitle: 'Megvan, köszönjük!',
    successBody: 'Átnézzük a jelentkezésed, és e-mailben keresünk.',
    again: 'Új jelentkezés',
    errorTitle: 'Nem sikerült elküldeni',
    errorBody: 'Nézd át a megjelölt kérdéseket, aztán próbáld újra.',
    throttled: 'Túl sok próbálkozás egyszerre. Várj egy percet, és küldd újra.',
    choose: 'Válassz…',
    step: '{current} / {total}',
  },
  hero: {
    eyebrow: 'Óbudai Egyetem · Budapest · Alapítva 2024',
    title1: 'Escape Velocity',
    title2: 'Rocketry',
    lede: 'Kísérleti rakétákat tervezünk, építünk és indítunk — a CAD-tól a próbapadig és a kilövésig.',
    ctaJoin: 'Csatlakozz',
    ctaMission: 'Küldetésünk',
    next: 'Következő',
    rocketAlt: 'A csapat kísérleti rakétája',
    unitDay: 'nap',
    unitHour: 'ó',
    unitMinute: 'p',
    unitSecond: 'mp',
    /*
      What the About section used to say, said in the hero's own empty
      band. Three lines, played in sequence as the stage is scrolled
      through — see Hero/HeroSays.vue. The <em> marks the one word that
      takes the accent.

      A fourth line is a translation edit plus a fourth window in
      Hero/anims.ts; the template does not change.
    */
    says: [
      'Egy rakétát nem érdekel a jó szándék. Csak az, ami <em>működik</em>.',
      'A CAD-tól a próbapadig. Aztán a <em>kilövésig</em>.',
      'Kilenc csoport, tizenkilenc ember, három rakéta. Egy már <em>repült</em>.',
    ],
  },
  nav: {
    programme: 'Amit építünk',
    mission: 'Küldetés',
    rocket: 'Rakéta',
    events: 'Események',
    about: 'Rólunk',
    team: 'Csapat',
    mentors: 'Mentorok',
    sponsors: 'Szponzorok',
    joinUs: 'Csatlakozz',
  },
  section: {
    events: 'Események',
    about: 'Rólunk',
    team: 'Csapat',
    mentors: 'Mentorok',
    sponsors: 'Szponzorok',
  },
  button: {
    viewMore: 'Továbbiak',
    becomeSponsor: 'Legyen szponzor',
    backToTop: 'Vissza a tetejére',
    home: 'Kezdőlap',
    back: 'Vissza',
  },
  outro: {
    contact: 'Kapcsolat',
    follow: 'Kövess minket',
    navigation: 'Navigáció',
    poweredBy: 'Támogatja',
  },
  placeholder: {
    comingSoon: 'Hamarosan',
    events: 'Az események részletes áttekintése hamarosan elérhető.',
    about: 'Bővebb történetünk hamarosan érkezik.',
    team: 'A teljes csapatlista hamarosan elérhető.',
  },
  about: {
    whoWeAre: 'Kik vagyunk',
    whoWeAreBody:
      'Az Escape Velocity Rocketry Student Team (EVRST) az Óbudai Egyetem hallgatói rakétacsapata. Kísérleti rakétákat tervezünk, építünk és indítunk, miközben a hallgatói mérnöki munka határait feszegetjük, és felkészülünk a nemzetközi megmérettetésekre.',
    projects: 'Projektek',
    goals: 'Célok',
    project1: {
      title: 'Atlas-1',
      description:
        'Első kísérleti rakétánk: kis magasságú tesztplatform az avionika, mentés és hajtóművek validálására.',
    },
    project2: {
      title: 'Helios',
      description:
        'Közepes teljesítményű rakéta 3 km-es csúcsmagasságra a fedélzeti számítógép és kettős mentés tesztelésére.',
    },
    project3: {
      title: 'Voyager',
      description: 'Hosszú távú cél: versenyképes rakéta nemzetközi diákversenyekre.',
    },
    goal1: {
      title: 'Mérnöki kiválóság',
      description:
        'Hallgatók képzése valós űrtechnikai feladatokon — a CAD-tól a próbapadig és a kilövésig.',
    },
    goal2: {
      title: 'Nemzetközi verseny',
      description: 'Az Óbudai Egyetem képviselete az EuRoC és a Spaceport America Cup versenyeken.',
    },
    goal3: {
      title: 'Inspiráció',
      description: 'Munkánk megosztása és a hazai diák-űrközösség erősítése.',
    },
  },
};

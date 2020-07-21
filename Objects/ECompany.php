<?php

/**
 * @brief Structure de données contenant les infos d'une entreprise
 */
class ECompany
{
    /**
     * Company ID in DB
     *
     * @var int
     */
    public $id;

    /**
     * The company's linkedin URL
     *
     * @var [string]
     */
    public $linkedIn;

    /**
     * The company's name
     * 
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $website;

    /**
     * @var string
     */
    public $phone;

    /**
     * The company's number of employees, taken as the upper bound of the linkedIn estimate (I.E 50-200 employees -> 200)
     *
     * @var int
     */
    public $nbEmployees;

    /**
     * The company's industry
     *
     * @var string
     */
    public $industry;

    /**
     * Description de l'entreprise
     *
     * @var string
     */
    public $desc;

    /**
     * Le type d'entreprise
     *
     * @var string
     */
    public $type;

    /**
     * L'année de fondation de l'entreprises
     *
     * @var int
     */
    public $year;

    /**
     * Les secteurs de spécialité de l'entreprise
     *
     * @var string
     */
    public $specialties;
    
    public function __construct($id)
    {
        $this->id = $id;
    }

    public function GetIndustry(){
        if(isset($this->industry))
            return $this->industry;
        else
            return false;
    }

    public function SetIndustry($industry, $isInFrench){
        if($isInFrench)
            $this->industry = ECompany::$industryTranslations[$industry];
        else
            $this->industry = $industry;
    }

    public function AsString()
    {
        $separator =";";
        $result = $this->website.$separator.$this->phone.$separator.$this->nbEmployees.$separator.$this->industry.$separator.$this->desc.$separator;
        return $result;
    }

    /**
     * List of industries in the format french => english
     *
     * @var AssociativeArray
     */
    public static $industryTranslations = array(
       "Défense et espace" =>"Defense & Space",
       "Matériel informatique" =>"Computer Hardware",
       "Logiciels informatiques" =>"Computer Software",
       "Réseaux informatiques" =>"Computer Networking",
       "Internet" =>"Internet",
       "Semi-conducteurs" =>"Semiconductors",
       "Télécommunications" =>"Telecommunications",
       "Avocats" =>"Law Practice",
       "Services juridiques" =>"Legal Services",
       "Conseil en management" =>"Management Consulting",
       "Biotechnologie" =>"Biotechnology",
       "Professions médicales" =>"Medical Practice",
       "Hôpitaux et centres de soins" =>"Hospital & Health Care",
       "Industrie pharmaceutique" =>"Pharmaceuticals",
       "Vétérinaire" =>"Veterinary",
       "Équipements médicaux" =>"Medical Devices",
       "Cosmétiques" =>"Cosmetics",
       "Confection et mode" =>"Apparel & Fashion",
       "Articles de sport" =>"Sporting Goods",
       "Tabac" =>"Tobacco",
       "Grande distribution" =>"Supermarkets",
       "Agro-alimentaire" =>"Food Production",
       "Produits électroniques grand public" =>"Consumer Electronics",
       "Biens de consommation" =>"Consumer Goods",
       "Meubles" =>"Furniture",
       "Commerce de détail" =>"Retail",
       "Divertissements" =>"Entertainment",
       "Jeux d’argent et casinos" =>"Gambling & Casinos",
       "Loisirs, voyages et tourisme" =>"Leisure, Travel & Tourism",
       "Hôtellerie et hébergement" =>"Hospitality",
       "Restaurants" =>"Restaurants",
       "Sports" =>"Sports",
       "Restauration collective" =>"Food & Beverages",
       "Industrie du cinéma" =>"Motion Pictures and Film",
       "Médias radio et télédiffusés" =>"Broadcast Media",
       "Musées et institutions culturelles" =>"Museums and Institutions",
       "Arts" =>"Fine Art",
       "Arts vivants" =>"Performing Arts",
       "Équipements et services de loisirs" =>"Recreational Facilities and Services",
       "Banques" =>"Banking",
       "Assurances" =>"Insurance",
       "Services financiers" =>"Financial Services",
       "Immobilier" =>"Real Estate",
       "Services d’investissement" =>"Investment Banking",
       "Gestion de portefeuilles" =>"Investment Management",
       "Comptabilité" =>"Accounting",
       "Construction" =>"Construction",
       "Matériaux de construction" =>"Building Materials",
       "Architecture et urbanisme" =>"Architecture & Planning",
       "Génie civil" =>"Civil Engineering",
       "Aéronautique et aérospatiale" =>"Aviation & Aerospace",
       "Industrie automobile" =>"Automotive",
       "Chimie" =>"Chemicals",
       "Machines et équipements" =>"Machinery",
       "Mines et métaux" =>"Mining & Metals",
       "Pétrole et énergie" =>"Oil & Energy",
       "Chantiers navals" =>"Shipbuilding",
       "Matières premières" =>"Utilities",
       "Industrie textile" =>"Textiles",
       "Industrie bois et papiers" =>"Paper & Forest Products",
       "Équipements ferroviaires" =>"Railroad Manufacture",
       "Agriculture" =>"Farming",
       "Élevage" =>"Ranching",
       "Secteur laitier" =>"Dairy",
       "Pêche" =>"Fishery",
       "Formation primaire/secondaire" =>"Primary/Secondary Education",
       "Enseignement supérieur" =>"Higher Education",
       "Administration scolaire et universitaire" =>"Education Management",
       "Études/recherche" =>"Research",
       "Armée" =>"Military",
       "Mandat législatif" =>"Legislative Office",
       "Institutions judiciaires" =>"Judiciary",
       "Affaires étrangères" =>"International Affairs",
       "Administration publique" =>"Government Administration",
       "Mandat politique" =>"Executive Office",
       "Police/gendarmerie" =>"Law Enforcement",
       "Sécurité civile" =>"Public Safety",
       "Politiques publiques" =>"Public Policy",
       "Marketing et publicité" =>"Marketing and Advertising",
       "Presse écrite" =>"Newspapers",
       "Édition" =>"Publishing",
       "Imprimerie, reproduction" =>"Printing",
       "Services d’information" =>"Information Services",
       "Bibliothèques" =>"Libraries",
       "Services pour l’environnement" =>"Environmental Services",
       "Messageries et fret" =>"Package/Freight Delivery",
       "Services à la personne" =>"Individual & Family Services",
       "Institutions religieuses" =>"Religious Institutions",
       "Associations et organisations sociales et syndicales" =>"Civic & Social Organization",
       "Services aux consommateurs" =>"Consumer Services",
       "Transports routiers et ferroviaires" =>"Transportation/Trucking/Railroad",
       "Entreposage, stockage" =>"Warehousing",
       "Compagnie aérienne/Aviation" =>"Airlines/Aviation",
       "Transports maritimes" =>"Maritime",
       "Technologies et services de l’information" =>"Information Technology and Services",
       "Études de marché" =>"Market Research",
       "Relations publiques et communication" =>"Public Relations and Communications",
       "Design" =>"Design",
       "Gestion des associations et fondations" =>"Non-Profit Organization Management",
       "Ingénierie du mécénat" =>"Fund-Raising",
       "Élaboration de programmes" =>"Program Development",
       "Contenus rédactionnels" =>"Writing and Editing",
       "Recrutement" =>"Staffing and Recruiting",
       "Formation professionnelle et coaching" =>"Professional Training & Coaching",
       "Capital-risque et fonds LBO" =>"Venture Capital & Private Equity",
       "Parti politique" =>"Political Organization",
       "Traduction et adaptation" =>"Translation and Localization",
       "Jeux électroniques" =>"Computer Games",
       "Organisation d’événements" =>"Events Services",
       "Arts et artisanat" =>"Arts and Crafts",
       "Industrie composants électriques/électroniques" =>"Electrical/Electronic Manufacturing",
       "Médias en ligne" =>"Online Media",
       "Nanotechnologies" =>"Nanotechnology",
       "Musique" =>"Music",
       "Logistique et chaîne d’approvisionnement" =>"Logistics and Supply Chain",
       "Plastiques" =>"Plastics",
       "Sécurité informatique et des réseaux" =>"Computer & Network Security",
       "Technologies sans fil" =>"Wireless",
       "Règlement extrajudiciaire de conflits" =>"Alternative Dispute Resolution",
       "Sécurité et enquêtes" =>"Security and Investigations",
       "Équipements collectifs" =>"Facilities Services",
       "Externalisation/délocalisation" =>"Outsourcing/Offshoring",
       "Santé, forme et bien-être" =>"Health, Wellness and Fitness",
       "Médecines alternatives" =>"Alternative Medicine",
       "Production audiovisuelle" =>"Media Production",
       "Films d’animation" =>"Animation",
       "Immobilier commercial" =>"Commercial Real Estate",
       "Marchés des capitaux" =>"Capital Markets",
       "Centres de recherches" =>"Think Tanks",
       "Humanitaire" =>"Philanthropy",
       "Formation à distance" =>"E-Learning",
       "Commerce de gros" =>"Wholesale",
       "Import et export" =>"Import and Export",
       "Ingénierie mécanique ou industrielle" =>"Mechanical or Industrial Engineering",
       "Photographie" =>"Photography",
       "Ressources humaines" =>"Human Resources",
       "Biens et équipements pour les entreprises" =>"Business Supplies and Equipment",
       "Secteur médico-psychologique" =>"Mental Health Care",
       "Design graphique" =>"Graphic Design",
       "Commerce et développement international" =>"International Trade and Development",
       "Vins et spiritueux" =>"Wine and Spirits",
       "Articles de luxe et bijouterie" =>"Luxury Goods & Jewelry",
       "Environnement et énergies renouvelables" =>"Renewables & Environment",
       "Verres, céramiques et ciments" =>"Glass, Ceramics & Concrete",
       "Emballages et conteneurs" =>"Packaging and Containers",
       "Automatismes industriels" =>"Industrial Automation",
       "Collectivités publiques et territoriales" =>"Government Relations",
    );
}

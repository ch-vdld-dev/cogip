<?php
namespace Cogip\Models;

use Cogip\Core\Db;

class Model extends Db
{
    // Table de la base de données
    protected $table;

    // Instance de connexion
    private $db;

    /**
     * Méthode qui exécutera les requêtes
     * @param string $sql Requête SQL à exécuter
     * @param array $attributes Attributs à ajouter à la requête 
     * @return PDOStatement|false 
     */
    public function requete(string $sql, array $attributs = null)
    {
        // On récupère l'instance de Db
        $this->db = Db::getInstance();

        // On vérifie si on a des attributs
        if($attributs !== null){
            // Requête préparée
            $query = $this->db->prepare($sql);
            $query->execute($attributs);
            return $query;
        }else{
            // Requête simple
            return $this->db->query($sql);
        }
    }

    /**
     * Sélection de tous les enregistrements d'une table
     * @return array Tableau des enregistrements trouvés
     */
    public function findAll($field='id', $orderBy='ASC')
    {
        $query = $this->requete('SELECT * FROM '.$this->table . " ORDER BY $field $orderBy");
        return $query->fetchAll();
    }

    /**
     * Sélection de plusieurs enregistrements suivant un tableau de critères
     * @param array $criteres Tableau de critères
     * @return array Tableau des enregistrements trouvés
     */
    public function findBy(array $criteres, $field='id', $orderBy='ASC')
    {
        $champs = [];
        $valeurs = [];

        // On boucle pour "éclater le tableau"
        foreach($criteres as $champ => $valeur){
            $champs[] = "$champ = ?";
            $valeurs[]= $valeur;
        }

        // On transforme le tableau en chaîne de caractères séparée par des AND
        $liste_champs = implode(' AND ', $champs);

        // On exécute la requête
        return $this->requete("SELECT * FROM {$this->table} WHERE $liste_champs ORDER BY $field $orderBy", $valeurs)->fetchAll();
    }

    /**
     * Sélection d'un enregistrement suivant son id
     * @param int $id id de l'enregistrement
     * @return array Tableau contenant l'enregistrement trouvé
     */
    public function find(int $id)
    {
        // On exécute la requête
        return $this->requete("SELECT * FROM {$this->table} WHERE id = $id")->fetch();
    }

    /**
     * Insertion d'un enregistrement suivant un tableau de données
     * @param Model $model Objet à créer
     * @return bool
     */
    public function create()
    {
        $champs = [];
        $inter = [];
        $valeurs = [];

        // On boucle pour éclater le tableau
        foreach($this as $champ => $valeur){
            // INSERT INTO annonces (titre, description, actif) VALUES (?, ?, ?)
            if($valeur != null && $champ != 'db' && $champ != 'table'){
                $champs[] = $champ;
                $inter[] = "?";
                $valeurs[] = $valeur;
            }
        }

        // On transforme le tableau "champs" en une chaine de caractères
        $liste_champs = implode(', ', $champs);
        $liste_inter = implode(', ', $inter);

        // On exécute la requête
        return $this->requete('INSERT INTO '.$this->table.' ('. $liste_champs.')VALUES('.$liste_inter.')', $valeurs);
    }

    /**
     * Mise à jour d'un enregistrement suivant un tableau de données
     * @param int $id id de l'enregistrement à modifier
     * @param Model $model Objet à modifier
     * @return bool
     */
    public function update()
    {
        $champs = [];
        $valeurs = [];

        // On boucle pour éclater le tableau
        foreach($this as $champ => $valeur){
            // UPDATE annonces SET titre = ?, description = ?, actif = ? WHERE id= ?
            if($valeur !== null && $champ != 'db' && $champ != 'table'){
                $champs[] = "$champ = ?";
                $valeurs[] = $valeur;
            }
        }
        $valeurs[] = $this->id;

        // On transforme le tableau "champs" en une chaine de caractères
        $liste_champs = implode(', ', $champs);

        // On exécute la requête
        return $this->requete('UPDATE '.$this->table.' SET '. $liste_champs.' WHERE id = ?', $valeurs);
    }

    /**
     * Suppression d'un enregistrement
     * @param int $id id de l'enregistrement à supprimer
     * @return bool 
     */
    public function delete(int $id){
        return $this->requete("DELETE FROM {$this->table} WHERE id = ?", [$id]);
    }

    /**
     * Hydratation des données
     * @param array $donnees Tableau associatif des données
     * @return any Retourne l'objet hydraté
     */
    public function hydrate($values)
    {
        foreach ($values as $key => $value){
            // On récupère le nom du setter correspondant à l'attribut.
            $method = 'set'.ucfirst($key);
            
            // Si le setter correspondant existe.
            if (method_exists($this, $method)){
                // On appelle le setter.
                $this->$method($value);
            }
        }
        return $this;
    }

    /**
     * Retourne une liste avec limit
     *
     * @param integer $limit Nombre d'enregistrement que l'on souhaite
     * @param string $order ASC ou DESC
     * @param string $by Champs sur lequelle doit etre trier la liste
     * @return object
     */
    public function limitBy(int $limit, string $order = 'ASC', string $by='id'){
        return $this->requete("SELECT * FROM {$this->table} ORDER BY {$by} {$order} Limit $limit")->fetchAll();
    }
}
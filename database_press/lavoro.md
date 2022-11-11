
# IN LAVORAZIONE V.2.0.1

feat: aggiungere i bottoni clona tabella, clona record.

BUG 
CREATE TABLE test_124 LIKE `wp_2_options`;
INSERT INTO test_124 SELECT * FROM `wp_2_options`;
dà errore!?!?

BUG con le tabelle union non gestisce il count con il numero delle righe (ma penso neanche il limit)

BUG autocomplete se non ci sono valori da selezionare e premo invio cancella tutto

FIX BUG search & replace.

# IN LAVORAZIONE: V.2.0.0
Ho diviso il progetto in più parti db-press e admin-form.

Ho provato a pubblicare intanto db-press.

Provo a pubblicare il progetto a pezzetti e tengo la versione database_press fuori dalla directory di wordpress...
Database_press ha tutto il blocco, ma è diviso in blocchetti al suo interno. 

ADMIN FORM e provo a pubblicarlo (solo nuove tabelle, niente frontend!, niente query, niente campi calcolati!!!).

CSV IMPORT/EXPORT (campi calcolati solo se c'è installato il template engine)

TEMPLATE ENGINE (?)

MANGAGE DB (?)
------------

ADVANCED ADMIN FORM: tutto il resto (database, template engine, frontend, backend-list, sql del setting).

---------------
## TODO FORM: 


BUG (FIXED - in parte): Provava a salvare dati di tabelle impostate come read only o tabelle CLOSE.
SE non era impostata readonly? da testare:
wps_dbp_connected (dbp_id=183) se provo a modificare e salvare un record ora mi scrive: "the data could not be saved" 

## FATTI FORM:
(FIXED) BUG: questo banale javascript  field.toggle(form.get('Mostra_campi').val() == 1); non funziona più! LIST #182 get accetta anche soltanto il nome del campo
(FATTO) FEAT javascript form field.name ritorna il riferimento del campo corrente 
(FIXED)  BUG: il media gallery required non funziona!
(REMOVED OPTION UPLOAD_FIELD) BUG: upload file in custom dir non funziona (PER ORA è stata eliminata l'opzione )
FEAT (FATTO):  Su field type text aggiungere accanto a required un'altra spunta "suggerimenti" di default spuntata. 

RIMOSSA la funzione deprecata process_saving_data_using_form_list

FEAT: in add new content non mostra i campi di sola lettura

FIX BUG: order e limit protetti da sql injection.


----------------- TODO V.1.0.0 -------------------------

Il tutorial deve essere migliorato

FATTO Servono più notifice nel salvataggio dei settings (quando fallisce la query il resto lo deve salvare). Quando un filtro non lo salvo devo avvertire. 

BUG (FIXED) sul form ripete due volte lo stesso campo "image sulla lista 138 galleries_images

BUG (FIXED) la galleria nel frontend non funziona?!? era global.dbp_id


-------
bug (fixed): Dbp::get_detail se non riceve l'id deve tornare vuoto e non sempre il primo record!

Guida a come realizzare una gallery

BUG (FIXED): Lookup params (non mostra il titolo!)

BUG (FIXED) Recalculate and save all records non funziona! al momento non fa riappaire l'errore ritornato

FATTO: i nuovi LOOKUP che si collegano alle tabelle.

TODO:
Ripulire javascript da lookup_sel_txt che non si usa più.

TODO da cambiare il namespace DatabasePress !!!

BUG FIXED [^IMAGE e [^POST se passi l'id che però non è un numero valido ritorna tutti i risultati invece che nessuno! 
ritornano troppi DATI!!!!

-----------------

BUG (FIXED) calculate field [^Image.title id=[%data.dbp_id]] torna i titoli di tutte le immagini, quindi non filtra per id!.
NON FILTRAVA PER ID PERCHÉ %data non era settato nelle liste... ora l'ho settato, **ma devo un po' decidermi** per le variabili come gestirle in modo più standard!

-----------------
IN LAVORAZIONE: Aggiunta la possibilità di passare custom attribute request nel detail per permettere l'inserimento di dati di default.

BUG (FIXED) la media gallery non si apre su nuovo plugin.

BUG Non funzionano i default su: media gallery. 

get_data_by_id (FATTO) diventa get_detail e estrae i dati a partire da un'id senza rielaborarli con list setting, ma con impostazioni del form (da gestire).

IN HOME PAGE mettere verifica se sei in DEBUG MODE e spiegare perché è da togliere
"In debug mode wordpress ti mostra un testo ogni volta che trova una query sbagliata!"

TODO:
Aggiungere path nel titolo solo se si attiva il link nell'admin
parametri de link: 
id della lista = valore +
parametro = valore, parametro = valore
Titolo (puoi usare pinacode [%data.title (con i parametri aggiunti)]);


-------------  fine versione 1.0.0 -------------------

V 1.0.1 pubblicazione su wordpress (- settembre -)

BUG sull'import se il titolo del csv ha un punto (.) allora non importa la colonna ?!

BUG: sull'edit:
20210504_portolano_2021 For subsequent forms, you can retrieve using shortcodes. Es: [%20210504_portolano_2021.undefined] (perché non trova la chiave primaria!!)

BUG: SELECT `dbp`.*, (SELECT COUNT(img.dbp_id) FROM `wps_dbp_galleries_images` img WHERE img.gallery = `dbp`.`dbp_id`) AS images FROM `wps_dbp_galleries` `dbp`

------------------------------------------------------

## Versione 1.1 (import/export e clone) OTTOBRE

Import e export della configurazione di una lista 
clone di un record di una lista
clone di una lista.
clone di una tabella.

import e export dei dati di una lista in csv

TODO Dare l'opzione per rimuovere l'autocomplete (sulla form e soprattutto se il campo è unique!)

TODO unique field

TODO  Creo uno short code per estrarre i dati di detail e get_data
pinacode: [^get_data id=XXX param=XXX]
[^get_dada.expires_at] e poi aggiungo gli attributi max, min e media(forse ci sono già?)
In pratica vorrei poter arrivare a riuscire a calcolare la data di un nuovo abbonamento. 
SE data < now allora now altrimenti la data massima.
 
## Versione 1.2 (Gestione delle tabelle collegate:) NOVEMBRE

Gestione delle tabelle collegate:
Esistono 2 possibili tipi di gestione: 
- un link all'elenco dei record In questo caso quando crei un lookup crea un parametro automatico di filtro admin. A questo punto si può creare negli elenchi di altre liste un sistema automatico di collegamento che mostra già l'elenco filtrato.
- Un link all'edit di un'altra lista. quindi clicchi e apre l'edit (Add/delete?) di un record di un'altra lista.

Backup DB completo?

## Versione 1.3 (Tabelle collegate Gestione avanzata dei metadati) DICEMBRE

TODO: Form possibilità di ordinare le tabelle.

Gestione migliore dei metadati nelle FORM con possibilità di aggiungere metadati. 

Gestione di inserimenti multipli per una tabella.

Possibilità di eliminare 


## Versione 1.4 (Campi editabili nelle liste) GENNAIO
 nuovi campi per le liste: campi per l'edit, campo per l'ordinamento delle righe (drag&drop), campo per l'inserimento dei file (drag&drop) (e possibilità di visualizzare questi campi anche nel frontend?!)

-----------------------------------------------------

 

TODO: Quando faccio un import SQL e si crea una nuova tabella dovrebbe mettere la tabella in DRAFT e segnalarla su (/class-dbp-table-admin.php:501) Visualizza la nuova tabella (linkabile).

TODO: IL DELETE DI UN POST DOVREBBE ESSERE FATTO DA POST_DELETE o comunque da funzioni specifiche per post

In PHP get_list è possibile passare parametri custom?
e nel template engine?
get_data c'è, lo inserisco nel template engine?
altri possibili tag: get_total, get_min, get_max, get_media, get_field?


-----------------------------------------------------
Miglioramento delle tabelle correlate (tipo ricarica se l'id di collegamento cambia, permetti di ordinarle, gestisci meglio i metadati ecc...)

------------------------------------------------------

Idee: quando creo una lista devo chiedere se voglio poterla modificare (appare/scompare il form e la visualizzazione nella sidebar (abilito/disabilito form edit e delete)).



TODO > Browser list aggiungere l'opzione "mostra tutto il testo"

Idee: Possibilità di gestire paginazione ordinamento e ricerca in javascript puro senza ajax se ci sono meno di 500 righe??


Idee: campi form: post multipli, utenti multipli, categorie e tag, password, email, votazione.


**TODO** Gestione messaggi: I messaggi adesso li gestisco in mixed con i cookie. Verificare dove devono essere gestiti.


IDEE: aggiungere il footer nelle liste con la possibilita di inserire i totali, le medie, il counter o testi con template engine e calcoli scritti ad hoc. Possibilità di fare questi calcoli per la pagina corrente oppure per tutti i dati.
Aggiungere la possibilità di invertire righe per colonne nel frontend.


IDEE EDITOR dei ruoli (alla fine fa parte sempre della gestione del DB!)

IDEE (Versione metadati): SUlla form in module type metto metadata se la tabella fa parte dei metadati e a quel punto lascio solo il metavalue visibile (gli cambio anche il nome e i campi sotto li elimino proprio?!).
Poi faccio le tabelle ordinabili all'interno del form e il bottone aggiungi metadati o rimuovi metadati che modifica la query.
Per le options praticamente lo stesso criterio (ma la query deve estrarre un solo record - da capire come (immagino non con left join ma semplicemente from collegati da virgola?!?))

********************************
Sul frontend vorrei arrivare a qualche cosa simile a questa:
https://producttable.barn2.com/
-------------------------------------------
fields types: https://docs.pods.io/fields/
-------------------------------------------




BUG  se creo una lista con più id e poi cancello gli id dalla query il sistema li riinserisce, ma nel frontend list li scrive doppi!






--------------------------------------------------------------------------
ROADMAP:

30/07/2022 -> DA qui solo bugfix e usabilità.

0.9  20 agosto (richiesta PUBBLICAZIONE) bugfix

1.0 10 settembre 

-------------------------

https://www.studiowombat.com/ dicono quanto guadagnano, mi stanno simpatici!

20.000 installazioni attive il loro prodotto top (ne avranno 40.000 installazioni attive in tutto...)

Sold 2,604 plugin licenses, compared to 1,230 in 2019. That’s twice as much. We have now sold 4,931 licenses in total.

-------------------------

https://wordpress.org/plugins/codepress-admin-columns/
https://wordpress.org/plugins/wp-data-access/
https://wordpress.org/plugins/database-browser/
https://wordpress.org/plugins/catch-ids/
https://wordpress.org/plugins/data-tables/
https://wordpress.org/plugins/export-user-data/
https://wordpress.org/plugins/search/tables/page/12/ todo
https://wordpress.org/plugins/data-tables-generator-by-supsystic/
https://wordpress.org/plugins/search-regex/
https://wordpress.org/plugins/wp-dbmanager/
https://wordpress.org/plugins/import-users-from-csv-with-meta/
https://wordpress.org/plugins/search-and-replace/
https://wordpress.org/plugins/advanced-access-manager/
--------------------------------------------

 - Una volta generati rielaboro gli shortcode del template engine.

Una complicazione che potrei togliere è get/post/ajax o almeno passarlo in codice come opzione. (PER ORA LO TENGO)


-------------

   
    - Quando si creano nuove tabelle queste possono essere raggruppate per categoria/progetto
    - Elenchi: campo automatico (elaborato dalla form)

           
    - I LOOKUP dovrebbero avere un link alla lista corrente?
    - I LOOKUP Cambiano la query? 
    - ADD METADATA possibilità di aggiungere campi personalizzati
    - FORM ADD METADATA il label è meta_value... sarebbe carino cambiarlo in automatico.

    - FORM OPZIONE 2 colonne

    - SINGOLE TABELLE: 
        - NASCOSTE, SOLO LETTURA, AGGIORNABILI ALLA MODIFICA DI UN CAMPO.
        - DUPLICABILI, ELIMINABILI > NO!

------------



 
**************************************************************************

Per ora bisogna pensarlo per un unico scopo: **Visualizza e modifica i dati estratti da una query!**
La versione finale deve avere un wizard che dice:
crea la tua tabella (o carica un csv) > inserisci/modifca i dati (o caricali o inserisci dati di esempio!) >  inserisci lo shortcode in un post (già creato in automatico)


- Gestione di metadati multipli?!

- Gestire le azioni nelle liste (devi poter scegliere sulla view/edit a che link mandare).


- Nella creazione FORM:

  - Aggiungere di gestire dalle liste i select, i checkboxes e il completamento automatico
  - Aggiungere possibilità di inserire nuove colonne.
  - Mostra nascondi colonne nascoste (?)
  - Verifiche delle impostazioni della form con tipo campi della tabella (es: su un numero non posso salvare una data o un testo).
  - Aggiungere moduli di connessione tipo altre tabelle, altre view

--------------- UPLOAD FILE --------------
https://stackoverflow.com/questions/21540951/custom-wp-media-with-arguments-support
------------------------------------------


CAMBIARE SCRITTA SULL'IMPORT CSV COME SEGUE:
Note: If you select a field for the PRIMARY ID, the matching entry with that ID or key will be updated.


**IDEA**: Bulk update field (fico, ma complicato perché se su tabella collegata deve poter gestire anche l'insert oltre update)!
apre la sidebar con il campo su cui fare l'update e la formula o il campo da cui prendere i dati e poi c'è il bottone esegui.

**IDEA** mettere tra i bottoni per la gestione della query uno che dice "Aggiungi campo calcolato" in cui metto alcune funzioni SQL




TODO:
Quando creo una tabella dbp deve SEMPRE aggiornare le options meta_value dbp_table_info. Anche quando carico da csv deve creare le opzioni. Quando rimuovo una tabella deve rimuovere le opzioni.
Queste poi devono poter essere modificate!
Nelle opzioni della tabella nella versione definitiva potrebbero esserci più campi

VERIFICARE private_add_name_request() se serve o se può essere eliminato.


-
-----------------------------------------
SQL SHOW/EDIT ROW:

Query di esempio:

SELECT * FROM  `wpx_posts` LEFT JOIN `wpx_postmeta` m01 ON m01.post_id = `wpx_posts`.`ID`

Mancano degli ID

SELECT post_author, post_title, meta_key AS label, m01.`meta_value` FROM `wpx_posts` LEFT JOIN `wpx_postmeta` m01 ON m01.post_id = `wpx_posts`.`ID`

Torna più risultati 

SELECT ID, post_author, post_title, meta_key AS label, m01.`meta_value` FROM `wpx_posts` LEFT JOIN `wpx_postmeta` m01 ON m01.post_id = `wpx_posts`.`ID`
-------------------------------------------

- Frontend list
    - Tabella
    - Editor
    - solo Hook (DA FARE)
Chiamate Pinacode a elenchi con parametri.
Chiamate shortcode a elenchi con parametri.
pinacode Ordinamento.
--------------------


Da verificare: pinacode nuovi shortcode per riprodurre le stesse opzioni della tabella o ricerche avanzate (shortcode per tabella, paginazione, ordinamento, ricerca, limite, link??, opzioni??, date?? (con la configurazione di wordpress).



PINACODE: TODO Mettere su tutte le action il check degli attributi pina_check_attributes


- Nel Browse nascondere la query editata dai filtri di ricerca


TODO Nelle liste serve una pagina di logging o Creare un tab per le liste che ne fa il debug se la lista è in draft mode, oppure salva eventuali errori gravi


---------------------------

**TAB struttura lista** ->
 Aggiungere la possibilità di mettere una relazione ad un'altra tabella (non attraverso la query ma estraendo tutti i dati con IN() e poi ricollegandoli). 
 Custom Code serve per capire cosa far apparire!?


 TAB STRUTTURA SQL
- FOREIGN KEY NON GESTITE!

# IDEE
BULK Per la query eseguita, possibilità di fare update, insert, delete o invio email o file in stile power automate
BULK data quality: per la query eseguita do a disposizione un editor pinacode che deve tornare true/false
e lo eseguo su tutta la tabella

su bulk dei checkbox al momento c'è delete o download. Potrei aggiungere edi che apre la sidebar laterale con i campi tutti disabilitati. Tramite checkbox si abilitano i campi e si può fare l'update bulk con il supporto di pinacode.

Sull'import l'update è limitato alla chiave primaria, sarebbe carino poter scegliere la chiave di update.

Sulle tabelle con primary id multiple se la query estrae dati da solo quella tabella devo poter gestire il CRUD e la ricerca


IDEA: Sull'edit se cambio l'id di un elemento ci potrebbe essere un bottone reload data che carica i dati di quell'id. 
poi potrebbe estendersi e dire che se quell'id è collegato da un altro campo di un'altra tabella i dati vengono aggiornati in automatico previa autorizzazione tramite popup (Esempio post e autori se cambio l'autore di un post in author_id allora cambia la scheda dell'edit dell'autore collegato


## PINACODE:
nuovi attributi
explode -> per un elemento o un array di elementi
get_firsts -> prende i primi n elementi
get_latest -> prende gli ultimi n elementi 

Attributi:  remove_extension rimuove il nome dell'estensione
            get_path in una stringa trova il percorso senza il nomefile

find_pos() -> trova la posizione di un elemento di un array

Aggiungere LIKE come operatore come = o != che deve valere sia per le tringhe che per gli array

---------------
TAB quality (PRO)


Query Monitor (https://wordpress.org/plugins/query-monitor/)
Optimize Database after Deleting Revisions https://wordpress.org/plugins/rvg-optimize-database/



# IDEE:


Aggiungere la possibilità di mettere i commenti per ogni colonna. Possibilità di esportare il codebook in csv
e reimportarlo


### TODO: TAB EXPORT NON LA FACCIO PER ORA PERCHÉ sostituita da bulk
-> Query con editor chiuso e bottone export CSV / SQL


```php 
    //ENTER THE RELEVANT INFO BELOW
    $mysqlUserName      = "Your Username";
    $mysqlPassword      = "Your Password";
    $mysqlHostName      = "Your Host";
    $DbName             = "Your Database Name here";
    $backup_name        = "mybackup.sql";
    $tables             = "Your tables";

   //or add 5th parameter(array) of specific tables:    array("mytable1","mytable2","mytable3") for multiple tables

    Export_Database($mysqlHostName,$mysqlUserName,$mysqlPassword,$DbName,  $tables=false, $backup_name=false );

    function Export_Database($host,$user,$pass,$name,  $tables=false, $backup_name=false )
    {
        $mysqli = new mysqli($host,$user,$pass,$name); 
        $mysqli->select_db($name); 
        $mysqli->query("SET NAMES 'utf8'");

        $queryTables    = $mysqli->query('SHOW TABLES'); 
        while($row = $queryTables->fetch_row()) 
        { 
            $target_tables[] = $row[0]; 
        }   
        if($tables !== false) 
        { 
            $target_tables = array_intersect( $target_tables, $tables); 
        }
        foreach($target_tables as $table)
        {
            $result         =   $mysqli->query('SELECT * FROM '.$table);  
            $fields_amount  =   $result->field_count;  
            $rows_num=$mysqli->affected_rows;     
            $res            =   $mysqli->query('SHOW CREATE TABLE '.$table); 
            $TableMLine     =   $res->fetch_row();
            $content        = (!isset($content) ?  '' : $content) . "\n\n".$TableMLine[1].";\n\n";

            for ($i = 0, $st_counter = 0; $i < $fields_amount;   $i++, $st_counter=0) 
            {
                while($row = $result->fetch_row())  
                { //when started (and every after 100 command cycle):
                    if ($st_counter%100 == 0 || $st_counter == 0 )  
                    {
                            $content .= "\nINSERT INTO ".$table." VALUES";
                    }
                    $content .= "\n(";
                    for($j=0; $j<$fields_amount; $j++)  
                    { 
                        $row[$j] = str_replace("\n","\\n", addslashes($row[$j]) ); 
                        if (isset($row[$j]))
                        {
                            $content .= '"'.$row[$j].'"' ; 
                        }
                        else 
                        {   
                            $content .= '""';
                        }     
                        if ($j<($fields_amount-1))
                        {
                                $content.= ',';
                        }      
                    }
                    $content .=")";
                    //every after 100 command cycle [or at last line] ....p.s. but should be inserted 1 cycle eariler
                    if ( (($st_counter+1)%100==0 && $st_counter!=0) || $st_counter+1==$rows_num) 
                    {   
                        $content .= ";";
                    } 
                    else 
                    {
                        $content .= ",";
                    } 
                    $st_counter=$st_counter+1;
                }
            } $content .="\n\n\n";
        }
        //$backup_name = $backup_name ? $backup_name : $name."___(".date('H-i-s')."_".date('d-m-Y').")__rand".rand(1,11111111).".sql";
        $backup_name = $backup_name ? $backup_name : $name.".sql";
        header('Content-Type: application/octet-stream');   
        header("Content-Transfer-Encoding: Binary"); 
        header("Content-disposition: attachment; filename=\"".$backup_name."\"");  
        echo $content; exit;
    }
```




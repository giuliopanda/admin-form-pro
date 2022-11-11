<?php
/**
 * Fa il parsing della parte Where di una query 
 * es .a>=b AND (.b<=var OR .c!=ccc L IN (3,2,5,3,52,34) ) OR(.c LIKE ("% ")  .d="[%pippo]") 
 * 
 * di default la congiunzione è AND ma se si vuole complicare il tutto si può aggiungere funzioni AND e OR che racchiudono tutte le query da eseguire
 * 
 * @return  ```php  
 *  'meta_query' => array(
 *        'relation' => 'OR',
 *         array(
 *            'key'     => 'color',
 *            'value'   => 'orange',
 *            'compare' => '=',
 *         ),
 *         array(
 *            'relation' => 'AND',
 *            array(
 *                    'key' => 'color',
 *                    'value' => 'red',
 *                    'compare' => '=',
 *            ),
 *            array(
 *                    'key' => 'size',
 *                    'value' => 'small',
 *                    'compare' => '=',
 *            ),
 *        ),
 *    ),
 * ```
 */

namespace DbPress;

if (!defined('WPINC')) die;

/**
 * Divide la stringa tra AND e OR e torna l'array filtrato. 
 */
function parse_query_string_fn($string, $base_relation = 'AND') {
    $fn = ['and ('=>0, 'and('=>0, 'or ('=>0, 'or('=>0];
    $start = 0;
    $op = "";
    $find_next = 99999;
    $new_str_array = array();
    foreach ($fn as $k=>$c) {
        if ($c <= $start) {
            $fn[$k] = stripos($string, $k, $start);
        }
        if ($fn[$k] === false) {
            unset($fn[$k]);
        } elseif ($find_next > $fn[$k] ) {
            $find_next = $fn[$k];
            $op = $k;
        }
    }
    if ($find_next == 99999) {
        //print ('<p>B string: '. $string."</p>");
        $array = parse_query_string2($string);
        if (!is_array($array) || count ($array) == 0) {
            return false;
        }
        $array['relation'] = $base_relation;
        //var_dump ($array);
        return $array;
    }
    //print "<p>".$find_next. " ".substr($string, $find_next, 6)."</p>";
    $b = pina_escape_all($string, $find_next, $find_next+7);
    if ($b != -1) {
        //print $b;
        if ($op == 'or (' || $op == 'or(') {
                $relation = "OR";
            } else {
                $relation = "AND";
            }
        $oplen = strlen($op);
        $new_str = substr($string,$find_next+$oplen,$b-$find_next-$oplen-1);
        $try_other = parse_query_string_fn($new_str, $relation);
        $other_str = substr($string,0, $find_next)." ".substr($string, $b);
        if (trim($other_str) != "") {
            //print ("<p>OTHER ".$other_str."</p>");
            $other_array = parse_query_string_fn($other_str, $base_relation);
            //var_dump ($other_array);
            //die;
            if (is_array($try_other) && count ($try_other) > 0) {
                if (!is_array($other_array)) {
                    $other_array[] = $try_other;
                } else {
                    $other_array[] = $try_other;
                } 
            }
        } else {
            return [$try_other];
        }
        //print ('<p>C new_str: '. $new_str."</p>");
        return $other_array;
    } else {
        //print ('<p>D OP: '. $op."</p>");
        if ($op == 'or (' || $op == 'or(') {
            $relation = "OR";
        } else {
            $relation = "AND";
        }
        $array = parse_query_string2($string);
        if (is_array($array)) {
            $array['relation'] = $relation;
            return $array;
        }
    }
    return false;
}




/**
 * Questa è la funzione più veloce che sono riuscito a scrivere che divide i parametri con gli operatori
 */


function parse_query_string2($string) {
    $compare =  [ ">" => 0,"<" => 0, "=" => 0, '!=' => 0, ">=" => 0, "<=" => 0,  "==" => 0, ' LIKE' => 0, ' NOT LIKE' => 0, ' IN' => 0, ' NOT IN' => 0, ' BETWEEN' => 0, ' NOT BETWEEN' => 0, ' NOT EXISTS' => 0, ' REGEXP', ' NOT REGEXP' => 0, ' RLIKE' => 0];
    $end_right = [" "=>0, "\n"=>0, "\r"=>0, "\t"=> 0];
    
    $len = strlen($string);
    $open_quote1 = $open_quote2 = $open_bracket = 0;
    $left = "";
    $meta_query = [];
    $start = 0;
    // se non ci sono è inutile controllarli
    foreach ($compare as $k=>$c) {
        $compare[$k] = strpos($string, $k);
        if ($compare[$k] === false) {
            unset($compare[$k]);
        }
    }
    

    foreach ($end_right as $k=>$c) {
        $end_right[$k] = strpos($string, $k);
        if ($end_right[$k] === false) {
            unset($end_right[$k]);
        }
    }
    do { 
        $find_next =$find_right = 9999;
        $op =  "";
        //print "<p>START: ".$start." (".substr($string, $start, 2).")</p>";
        foreach ($compare as $k=>$c) {
            if ($c < $start) {
                $compare[$k] = strpos($string, $k, $start);
            }
            if ($compare[$k] === false) {
                unset($compare[$k]);
            }  elseif ($find_next >= $compare[$k] ) {
                $find_next = $compare[$k];
                $op = $k;
            }
        }
        
        if ($op != "" ) {            
            $left = substr($string, $start, $find_next -  $start );
            while ($left != ($left = trim($left)));
            $start = $find_next+strlen($op);

            // is right
            // prima di tutto trovo il primo carattere che non è uno spazio 
            while ((@$string[$start] == " " || @$string[$start] == "\n" || @$string[$start] == "\t" || @$string[$start] == "\r")) {
                $start++;    
            } 
            
            do {
                foreach ($end_right as $k=>$c) {
                    if ($c < $start) {
                        $end_right[$k] = strpos($string, $k, $start);
                    }
                    if ($end_right[$k] === false) {
                        unset($end_right[$k]);
                    } elseif ($find_right > $end_right[$k] ) {
                        $find_right = $end_right[$k];
                    }
                }
                // solo se il primo carattere è un ( [ " ' allora finisco di elaborarlo e mi aspetto che a fine elaborazione ci sia ) ] " ' e non deve esserci il while!
                $new_right = pina_escape_all($string, $start, $find_right);
                $new_right = 0;
                if ($new_right > $find_right ) {
                    $start = $new_right;
                }
            } while ($new_right > $find_right);
            if ($find_right != "" ) {            
                $right = substr($string, $start, $find_right -  $start );
                $start = $find_right;    
                $meta_query[] =  array(                
                'key' => $left,
                'value' => $right,
                'compare' => $op);    
                $left = "";        
            } else {
                $meta_query[] =  array(                
                    'key' => $left,
                    'value' => substr($string, $start),
                    'compare' => $op);    
                break;    
            }
        } else {
            break;
        }
    } while ($left == "" && $start < $len-1);
    return $meta_query; 
}



 /**
  * Torna -1 se non trova nulla da saltare oppure il numero di caratteri dove finiscono le parentesi da saltare.
  * lo spazio da controllare è tra lo start e il cursore per  i blocchi da escludere () [] "" ''
  * @param int $cursor il punto dove c'è lo spazio successivo allo start
  * @param int $start il punto da cui si deve iniziare a guardare
  * @return int
  */
function pina_escape_all($string, $start, $cursor) {
    $excl1 = ["("=>strpos($string, "(", $start),"["=>strpos($string, "[", $start),"'"=>strpos($string, "'", $start),'"'=>strpos($string, '"', $start),")"=>-1,"]"=>-1 ];
    if (($excl1["("] === false || $cursor < $excl1["("]) && ($excl1["["] === false || $cursor < $excl1["["]) && ($excl1["'"] === false || $cursor < $excl1["'"]) && ($excl1['"'] === false || $cursor < $excl1['"']) ) {
        return -1;
    } else {
        if (($excl1["("] === false || $cursor < $excl1["("])) {
            unset($excl1["("]);
            unset($excl1[")"]);
        }
        if (($excl1["["] === false || $cursor < $excl1["["])) {
            unset($excl1["["]);
            unset($excl1["]"]);
        }
        if (($excl1["'"] === false || $cursor < $excl1["'"])) {
            unset($excl1["'"]);
        }
        if (($excl1['"'] === false || $cursor < $excl1['"'])) {
            unset($excl1['"']);
        }
        $open_exclude = ["("=>0,"["=>0,"'"=>0,'"'=>0];        
        // parto
        $new_start = $start;
        $count_do = 0;
        do {
            $find_next = 99999;
            foreach ($excl1 as $k=>$x) {
                if ($x < $new_start && $x !== false) {
                    $excl1[$k] = strpos($string, $k, $new_start);
                }
                if ($excl1[$k] !== false && $find_next >= $excl1[$k] ) {
                    $find_next = $excl1[$k];
                    $tk = $k;
                }
            }
            if ($find_next == 99999) {
                break;
            }
            if ($tk == "(" || $tk == "[") {
                $open_exclude[$tk]++;
            }
            if ($tk == ")") {
                $open_exclude['(']--;
            }
            if ($tk == "]") {
                $open_exclude['[']--;
            }
            if ($tk == '"') {
                if ($string[$find_next-1] != "\\") {
                    $open_exclude['"'] = 1 - $open_exclude['"'];
                }
            }
            if ($tk == "'") {
                if ($string[$find_next-1] != "\\") {
                    $open_exclude["'"] = 1 - $open_exclude["'"];
                }
            }
            $new_start = $find_next+1;
            //print "<p>".$tk ." = ".$new_start."</p>";
        } while (($open_exclude['('] > 0 || $open_exclude['['] > 0 || $open_exclude['"'] > 0 ||  $open_exclude["'"] > 0 ) && $count_do++ <10);
        
        if ($open_exclude['('] > 0 || $open_exclude['['] > 0 || $open_exclude['"'] > 0 ||  $open_exclude["'"] > 0 ) {
            // ERRORE!
        }
    }
    return $new_start;
}



/* TEST



$start = microtime(true);
$test = parse_query_string_fn('jlkj=8  AND(.a>=b 
                OR( .b<=var 
                    .c!=ccc L IN (3,2,5,3,52,34) )
                .c LIKE ("% ") 
                .d="[%pippo]" 
                parametro= )mljklkjljhlk
');

$test = parse_query_string_fn('AND(.a>=b 
                 .b<=var 
                OR( .b<=var 
                    .c!=ccc L IN (3,2,5,3,52,34) )
                .c LIKE ("%pioipo%") )
                
                
');

$test = parse_query_string_fn('AND(.a>=b 
                 .b<=var 
                
                .c LIKE ("%pioipo%") )
                
                
');

echo '<pre>';
print_r($test);
echo '</pre>';
print "<p>".(microtime(true)-$start)."</p>";
die();
/** comparazione tra parse_query_string1 e 2
per 100 stringhe elaborate:
1 = da 1.80 a 1.88
2 = da 0.73 a 0.79


$start = microtime(true);
for ($x = 0; $x < 100; $x++) {
pina_escape_all("lorem ipsum kòlkòl kòl òlkò l kòlkòlk ò as ss  lkkljjlk jlkjkl )jlkj lkj", 0, 23);
pina_escape_all("lorem ipsum ( a(s(ss ) lk\"kljjlk jlkjkl )jlkj\" lkj", 15, 23);
pina_escape_all("
m ( a(s(ss ) lk\"m ( a(s(ss ) lk\" orem ipsum kòlkòl kòl òlkò lorem ipsum kòlkòl kòl òlkò lorem ipsum kòlkòl kòl òlkò lorem ipsum kòlkòl kòl òlkò l
 )jlkj\" lkj", 0, 23);
}
print "<p>".( microtime(true)-$start)."</p>";
die;

$start = microtime(true);
$toto = 0;
for ($x = 0; $x < 100; $x++) {
    $test = parse_query_string2('AND ( .a>=l 
    .b <= var .c!=ccc  parametro  = 
    kòlkklòkòl .a AND >= b) AND ( .bkl!=ccc  post_title  = 
    pippo_pluto_paperino .a  
    >=   12 389
    .b <= var  .c!=ccc  parametro  = 
    kòlkklòkòl ) OR ( .a>=b .b <= var  .c!=ccc  parametro  = 
    kòlkklòkòl .ed!=sd .asd >= ppi dad == djklasjdlksajldkas .a>=b
    .b <= var  .c!=ccc  parametro  = 
    kòlkklòkòl .a>=b .bkl!=ccc  post  = ( jlj pippo_pluto_paperino) .a>=12389
    .b <= var lkja>=b
    .b <= var  .c!=ccc  parametro  = 
    kòlkklòkòl .ed!=sd .asd >= ppi dad == djklasjdlksajldkas');
    
    //print ("Tempo di esecuzione: ". (microtime(true)-$start)."<br>");
}
print "<p>".(microtime(true)-$start)."</p>";
//var_dump ($test);
die;
//$test = parse_query_string('.a>=b AND (.b<=var OR .c!=ccc L IN (3,2,5,3,52,34) ) .c LIKE ("% ") AND .d="[%pippo]" parametro=');

*/
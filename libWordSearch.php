<?

function sortByLength ($a, $b) {
    if (strlen($a) == strlen($b)) return 0;
    return (strlen($a) > strlen($b)) ? -1 : 1;
}

function lengthOfLongestElement (&$array) {
   usort ($array, 'sortByLength');
   return strlen($array[0]);
}

class WordSearch {
   var $MAXWIDTH   = 40;                /* 79 cols / 2 (to fit on CRT or printer */
   var $SIZEFUDGE  = 3;                 /* Make the array smaller by this amount */
   var $NUMOFFIT   = 10;
   var $arrayOfColorsForWord = array();
   
   function WordSearch ($array)
   {
      usort ($array, 'sortByLength');
      $this->origArrayOfWords = $this->arrayOfWords = $array;
      $this->build_color_array ();
      $this->build();
   }
   
   function build_color_array ()
   {

   for($m = 0; $m < 42; $m++) {
      // Line 1: red = 0 ; green = 0 -> 255 ; blue = 255
      $this->arrayOfColors[$m] 		= $this->rgb2hex(160, 160, 160);
      $this->arrayOfColors[$m + 20] 	= $this->rgb2hex(160, 160, 160);
      $this->arrayOfColors[$m + 40] 	= $this->rgb2hex(160, 160, 160);
      $this->arrayOfColors[$m + 60] 	= $this->rgb2hex(160, 160, 160);
   }

      
   }
   
   function rgb2hex ($red, $green, $blue) {
      return sprintf("%02X%02X%02X",$red,$green,$blue);
   }
   
   function build ()
   {
      (int) $col;
      (int) $count;
      (int) $length;
      (int) $longest;
      (int) $nwords;
      (int) $width;
/*
* The first task is to guess at the size of the final board.  One
* dimension must be at least as long as the longest word.  However,
* in practicE...
*/
      $this->width  = 10;
      $this->length = 20;
/*
* Make the puzzle artificially smaller.  This means that if the word
* fitting algorithm happens to have a good day, the puzzle can be
* smaller than the sum of all of the characters.  On the other hand,
* making it too small would cause extra work by forcing the board
* to grow repeatedly.  Note that there is no check to make sure that
* the longest word will fit.
*/
      $this->width  = 20;
      $this->length = 10;

/*
* It looks silly when all of the words are lined up.  Force
* variation in the direction at the expense of larger puzzles.
*/
      $this->dirvec2[3] = $this->dirvec2[4] = $this->dirvec2[1] = $this->dirvec2[6] = -1; //$this->rndint(0, -1); //$nwords - 1;
      $this->dirvec2[0] = $this->dirvec2[2] = $this->dirvec2[5] = $this->dirvec2[7] = 1; //$this->rndint(1, 0); //$nwords + 1;
      $this->dirvec = $this->dirvec2;
      
      sort ($this->dirvec);
      sort ($this->dirvec2);
/*
* Now it is time to assign words into the puzzle.  Assign the longest
* words first, as the smaller words might have a better chance of
* fitting between words than big words.
*
* Attempt to fit the word into the puzzle by sharing the most letters
* possible with other words.  If the word cannot share letters,
* try to fit it between the other words.  If that doesn't work,
* then the puzzle must grow.
*
* When the puzzle grows, all of the words must be reassigned to
* avoid leaving the edges mostly unused.
*/

      while (true) {
         if (count($this->arrayOfWords) == 0) {
            break;
         }

         foreach ($this->arrayOfWords as $index => $word) {
         /* See if it will fit on the board */

            if (!$this->fit($index)) {
               /* It doesn't fit, so grow the board a little		*/
               $this->grow();
               $this->dirvec = $this->dirvec2;
            }
         }
      }
   }

   function rndint ($max, $min = 0)
   {
      (int) $this->got_seed = FALSE;
      (double) $seed = (double) microtime() * 1000000;

      if (!$this->got_seed) {
         $this->got_seed = TRUE;
         $this->seed = $seed;
         mt_srand($this->seed);
      }
      
      return (rand($min, $max));
   }
   
   function rndchar ()
   {
      return strtoupper(chr(mt_rand(97,122)));
   }

   function grow ()
   {
      (int) $col;
      (int) $count;

      /* Make the puzzle a bit larger					*/
      if ($this->width = $this->MAXWIDTH) {
         $this->width++;
         $this->length++;
         //$this->puzzle = NULL;
         //$this->arrayOfWords = $this->origArrayOfWords;
      }
   }
/*
* fit
* Attempt to fit the word into the puzzle
* Start at a random place on the board.  Go through all possible board
* positions and directions looking for the first position where the
* word would fit with the most shared letters.  Use that position.
*/
   function fit ($index)
   {
      $word = trim($this->arrayOfWords[$index]);
      $directions = array ( -1, -1,
                            -1, 0,
                            -1, 1,
                            0, -1,
                            0, 1,
                            1, -1,
                            1, 0,
                            1, 1);

      (int)    $best_count;   /* Number of collisions in best guess	*/
      (string) $ch;           /* Current character in puzzle		*/
      (int)    $count;        /* Count through the word		*/
      (int)    $dir_best;	   /* Best direction for the guess		*/
      (int)    $dir_org;      /* First direction			*/
      (int)	   $dir_scan;     /* Current direction			*/
      (int)    $l_best;
      (int)    $w_best;	/* Best guess				*/
      (int)    $l_delta;
      (int)    $w_delta; /* Delta multipliers			*/
      (int)    $l_org;
      (int)    $w_org;	/* First guess				*/
      (int)    $l_scan;
      (int)    $w_scan;	/* Current guess			*/
      (int)    $len;		/* Length of the word			*/
      (int)    $scan_count;	/* Number of collisions in current guess */
      (int)    $tl;
      (int)    $tw;		/* Temporary board positions		*/

/* Choose the initial board position				*/
      $w_scan = $w_org = $this->rndint($this->width-1);
      $l_scan = $l_org = $this->rndint($this->length-1);
      $len = strlen($word);
      $best_count = -1;
/* Scan the entire board						*/
      do {
         $dir_scan = $dir_org = $this->rndint(8);	/* Get the starting position	*/
         do {
            if ($this->dirvec[$dir_scan] > 0) {
		/* Fetch the deltas from the direction table		*/
               $l_delta = $directions[$dir_scan * 2];
               $w_delta = $directions[$dir_scan * 2 + 1];
      /*
		* See if the word will fit at the current position, in
		* the current direction.  Check the last character to ensure
		* that the word fits on the board.
		*/
               $scan_count = -1;
               if ( ($tl = $l_scan + $l_delta * ($len - 1)) >= 0 && $tl < $this->length && ( $tw = $w_scan + $w_delta * ($len - 1)) >= 0 && $tw < $this->width) {
                  /* See if the word can work in the puzzle		*/
                  $scan_count = 0;
                  for ($count = 0; $count < strlen($word); $count++) {
                     $ch = $this->puzzle[$l_scan + $l_delta * $count] [$w_scan + $w_delta * $count];
                     if ($ch == $word{$count}) {
                        $scan_count++;
                     } elseif ($ch != '') {
                        $scan_count = -1;
                        break;
                     }
                  }
               }

      /*
		* Make sure that the number of shared
		* characters is less than the length of the word.  This isn't
		* perfect, as it disallows some assignments where the word
		* is entirely shared with more than one other word.  However,
		* this is easier than remembering where words were laid down.
		*/
		
               if ($scan_count > $best_count && $scan_count < $len) {
                  $best_count = $scan_count;
                  $w_best = $w_scan;
                  $l_best = $l_scan;
                  $dir_best = $dir_scan;
               }
            }
      /* Choose the next direction				*/
            if (++$dir_scan == 8)
               $dir_scan = 0;
               
         } while (($dir_scan != $dir_org) && ($copput++ < 1));

         /* Bump the board position					*/
         if (++$w_scan == $this->width) {
            $w_scan = 0;
         }
         

         if (++$l_scan == $this->length) {
               $l_scan = 0;
         }
      } while (($w_scan != $w_org) || ($l_scan != $l_org));

      /* If we didn't find anyplace for the word, we failed		*/
      if ($best_count == -1 && $this->numOfTrys++ > $this->NUMOFFIT) { //($this->width * $this->length)) {
         $this->numOfTrys = 0;
         return (FALSE);
      } elseif ($best_count == -1) {
         return $this->fit ($index);
      }

      /* We got a match.  Put the word in					*/
      $l_delta = $directions[$dir_best * 2];
      $w_delta = $directions[$dir_best * 2 + 1];
      $this->dirvec[$dir_best]--;
      for ($count = 0; $count < $len; $count++) {
         //$this->puzzle[$l_best + $l_delta * $count] [$w_best + $w_delta * $count] = '<td bgcolor=#'.$this->arrayOfColors[$index].'>'.$word{$count}.'</td>';
         $this->puzzle[$l_best + $l_delta * $count] [$w_best + $w_delta * $count] = '<td class="letter word_'.trim($this->arrayOfWords[$index]).'">'.$word{$count}.'</td>';
         $this->arrayOfColorsForWord[trim($this->arrayOfWords[$index])] = $this->arrayOfColors[($index * 8) % 255];
         //      $this->puzzle[$l_best + $l_delta * $count] [$w_best + $w_delta * $count] = '<td>'.$word{$count}.'</td>';

      }

      $count--;
      //printf("Assigned \"%s\" from %d %d to %d %d (shared %d, ld %d wd %d)\n",
      //$word, $l_best, $w_best, $l_best + $l_delta * $count,
      //$w_best + $w_delta * $count, $best_count, $l_delta, $w_delta);
      unset ($this->arrayOfWords[$index]);
      return (TRUE);		/* Got it */
   }
}


?>

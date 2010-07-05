<?php
/********************************
OSBib:
A collection of PHP classes to manage bibliographic formatting for OS bibliography software 
using the OSBib standard.  Taken from WIKINDX (http://wikindx.sourceforge.net).

Released through http://bibliophile.sourceforge.net under the GPL licence.
Do whatever you like with this -- some credit to the author(s) would be appreciated.

If you make improvements, please consider contacting the administrators at bibliophile.sourceforge.net 
so that your improvements can be added to the release package.

Mark Grimshaw 2005
http://bibliophile.sourceforge.net
********************************/
class PARSESTYLE
{
	function PARSESTYLE()
	{
	}
// parse input into array
	function parseStringToArray($type, $subject, $map = FALSE)
	{
		if(!$subject)
			return array();
		if($map)
			$this->map = $map;
		$search = join('|', $this->map->$type);
		$subjectArray = split("\|", $subject);
// Loop each field string
		$index = 0;
		$independentFound = FALSE;
		foreach($subjectArray as $subject)
		{
			$dependentPre = $dependentPost = $dependentPreAlternative = 
				$dependentPostAlternative = $singular = $plural = FALSE;
// First grab fieldNames from the input string.
			preg_match("/(.*)(?<!`|[a-zA-Z])($search)(?!`|[a-zA-Z])(.*)/", $subject, $array);
			if(empty($array))
			{
				if($independentFound)
				{
					$independent['independent_' . ($index - 1)] = $subject;
					$independentFound = FALSE;
				}
				else
				{
					$independent['independent_' . $index] = $subject;
					$independentFound = TRUE;
				}
				continue;
			}
// At this stage, [2] is the fieldName, [1] is what comes before and [3] is what comes after.
			$pre = $array[1];
			$fieldName = $array[2];
			$post = $array[3];
// Anything in $pre enclosed in '%' characters is only to be printed if the resource has something in the 
// previous field -- replace with unique string for later preg_replace().
			if(preg_match("/%(.*)%(.*)%|%(.*)%/U", $pre, $dependent))
			{
// if sizeof == 4, we have simply %*% with the significant character in [3].
// if sizeof == 3, we have %*%*% with dependent in [1] and alternative in [2].
				$pre = str_replace($dependent[0], "__DEPENDENT_ON_PREVIOUS_FIELD__", $pre);
				if(sizeof($dependent) == 4)
				{
					$dependentPre = $dependent[3];
					$dependentPreAlternative = '';
				}
				else
				{
					$dependentPre = $dependent[1];
					$dependentPreAlternative = $dependent[2];
				}
			}
// Anything in $post enclosed in '%' characters is only to be printed if the resource has something in the 
// next field -- replace with unique string for later preg_replace().
			if(preg_match("/%(.*)%(.*)%|%(.*)%/U", $post, $dependent))
			{
				$post = str_replace($dependent[0], "__DEPENDENT_ON_NEXT_FIELD__", $post);
				if(sizeof($dependent) == 4)
				{
					$dependentPost = $dependent[3];
					$dependentPostAlternative = '';
				}
				else
				{
					$dependentPost = $dependent[1];
					$dependentPostAlternative = $dependent[2];
				}
			}
// find singular/plural alternatives in $pre and $post and replace with unique string for later preg_replace().
			if(preg_match("/\^(.*)\^(.*)\^/U", $pre, $matchCarat))
			{
				$pre = str_replace($matchCarat[0], "__SINGULAR_PLURAL__", $pre);
				$singular = $matchCarat[1];
				$plural = $matchCarat[2];
			}
			else if(preg_match("/\^(.*)\^(.*)\^/U", $post, $matchCarat))
			{
				$post = str_replace($matchCarat[0], "__SINGULAR_PLURAL__", $post);
				$singular = $matchCarat[1];
				$plural = $matchCarat[2];
			}
// Now dump into $final[$fieldName] stripping any backticks
			if($dependentPre)
				$final[$fieldName]['dependentPre'] = $dependentPre;
			else
				$final[$fieldName]['dependentPre'] = '';
			if($dependentPost)
				$final[$fieldName]['dependentPost'] = $dependentPost;
			if($dependentPreAlternative)
				$final[$fieldName]['dependentPreAlternative'] = $dependentPreAlternative;
			else
				$final[$fieldName]['dependentPreAlternative'] = '';
			if($dependentPostAlternative)
				$final[$fieldName]['dependentPostAlternative'] = $dependentPostAlternative;
			else
				$final[$fieldName]['dependentPostAlternative'] = '';
			if($singular)
				$final[$fieldName]['singular'] = $singular;
			else
				$final[$fieldName]['singular'] = '';
			if($plural)
				$final[$fieldName]['plural'] = $plural;
			else
				$final[$fieldName]['plural'] = '';
			$final[$fieldName]['pre'] = str_replace('`', '', $pre);
			$final[$fieldName]['post'] = str_replace('`', '', $post);
			$index++;
			$final[$fieldName]['pre'] = $pre;
			$final[$fieldName]['post'] = $post;
		}
		if(!isset($final)) // presumably no field names...
			$this->badInput($this->errors->text("inputError", "invalid"), $this->errorDisplay);
// last element of odd number is actually ultimate punctuation
		if(isset($independent) && sizeof($independent) % 2)
			$final['ultimate'] = array_pop($independent);
		if(isset($independent) && !empty($independent))
			$final['independent'] = $independent;
		return $final;
	}
}
?>

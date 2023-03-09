<?php
namespace twentyseconds\validation;

/**
 * evaluator
 *
 * evaluieren von validator bedingungen (conditions)
 *
 * unterstützt werden folgende einfache vergleiche von objektattributen
 *
 * =, ==, eq   # vergleich auf gleichheit
 * <, lt       # kleiner als
 * >, gt       # größer als
 * !=, ne, not # ungleichheit
 * 
 *
 * @package ols
 * @author Robert Wagner
 * @see validator.class.php
 **/
class evaluator{
   
   static function check($expr, $context, $el=null){
      log_error("[EVAL] check expr $expr");
      if($expr[0]==":"){
         $meth = substr($expr, 1);
         $res = $context->$meth($el);
         log_error("[EVAL] METHOD $meth = $res");
         return $res;
      }
      
	   list($field1, $op, $field2) = explode(" ", trim($expr));
	   $a = self::value($field1, $context);
	   $b = self::value($field2, $context);
	   log_error("[EVAL] check $a VS $b");
	   switch($op){
	      case "=":
	      case "==":
	      case "eq":
	         return $a == $b;
	      case "<":
	      case "lt":
	         return $a < $b;
	      case "<=":
	      case "le":
	         return $a <= $b;
	      case ">":
	      case "gt":
	         return $a > $b;
	      case ">=":
	      case "ge":
	         return $a >= $b;
	      case "!=":
	      case "ne":
	      case "not":
	         return $a != $b;
	      default:
	         return false;
	   }
   }
   
   static function check_js($expr, $context, $el=null){
      log_error("[JS-EVAL] check expr");
      log_error($expr);

      if($expr[0]==":"){
         return false;
         
         
         $meth = substr($expr, 1);
         return $context->$meth($el);
      }
      
	   list($field1, $op, $field2) = explode(" ", trim($expr));
	   $a = self::value_js($field1, $context);
	   $b = self::value_js($field2, $context);
	   log_error("[JS-EVAL] check $a VS $b");
	   
	   // function(el) {return $("#contactform_email:checked")}
	   
      // function(el) {return jQuery('#$field').val() == '004'}
	   
	   $muster = 'function() {return %s %s %s;}';
	   
	   if($op == '=='){
	      return sprintf($muster, $a, $op, $b);
	   }elseif($op == 'checked'){
			return sprintf($muster, $a.'.is(":checked")', '', '');
		}elseif($op == 'unchecked'){
			return sprintf($muster, '!'.$a.'.is(":checked")', '', '');
		}
	   
	   return false;
	   
	   switch($op){
	      case "=":
	      case "==":
	      case "eq":
	         return $a == $b;
	      case "<":
	      case "lt":
	         return $a < $b;
	      case "<=":
	      case "le":
	         return $a <= $b;
	      case ">":
	      case "gt":
	         return $a > $b;
	      case ">=":
	      case "ge":
	         return $a >= $b;
	      case "!=":
	      case "ne":
	      case "not":
	         return $a != $b;
	      default:
	         return false;
	   }
   }

   static function value($field, $context){
      if($field[0]=='$'){
         $field=substr($field, 1);
         return $context->$field;
      }
      return $field;
   }
   
   static function value_js($field, $context){
      if($field[0]=='$'){
         $field=substr($field, 1);
         return "jQuery('#$field').val()";
      }
      return "'$field'";
   }
   
}

?>
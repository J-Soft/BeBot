<?php
/*
* Calc.php - Simple calculations extended for continous calculations by Wolfbiter
*
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2010 Thomas Juberg, ShadowRealm Creations and the BeBot development team.
*
* Developed by:
* - Alreadythere (RK2)
* - Blondengy (RK1)
* - Blueeagl3 (RK1)
* - Glarawyn (RK1)
* - Khalem (RK1)
* - Naturalistic (RK1)
* - Temar (RK1)
*
* See Credits file for all aknowledgements.
*
*  This program is free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; version 2 of the License only.
*
*  This program is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  You should have received a copy of the GNU General Public License
*  along with this program; if not, write to the Free Software
*  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307
*  USA
*/
$Calc = new Calc($bot);
/*
The Class itself...
*/
class Calc extends BaseActiveModule
{

	function __construct(&$bot)
	{
		parent::__construct($bot, get_class($this));
		$this->register_command('all', 'calc', 'GUEST');
		$this->bot->core("settings")->create("Calc", "ShowEquation", TRUE, "Should the equation be shown when doing calculations?");
		$this->help['description'] = "Performs simple calculations";
		$this->help['command']['calc <expression>'] = "Shows the result of the matematical <expression>";
		$this->calcu = array();
	}

	/*
	This gets called on a tell with the command
	*/
	function command_handler($name, $msg, $origin)
	{
		if (preg_match("/^calc (.+)/i", $msg, $info))
			return $this->do_calcs($name, $info[1]);
		elseif (preg_match("/^calc$/i", $msg))
			return $this->show_calc($name);
		else
			$this->bot->send_help($name);
		return false;
	}

	/*
	Does the evaluation and calculation
	*/
	function do_calcs($name, $calc)
	{
		$test = str_replace(".", "", $calc);
		$test = str_replace(",", "", $test);
		$test = str_replace("+", "", $test);
		$test = str_replace("-", "", $test);
		$test = str_replace("*", "", $test);
		$test = str_replace("/", "", $test);
		$test = str_replace("\\", "", $test);
		$test = str_replace("x", "", $test);
		$test = str_replace("X", "", $test);
		$test = str_replace("%", "", $test);
		$test = str_replace("(", "", $test);
		$test = str_replace(")", "", $test);
		$test = str_replace(" ", "", $test);
		if (is_numeric($test))
		{
			if (preg_match('|^([\+\*/\-%])|', $calc, $matches) && isset($this->calcu[$name]))
			{
				$temp = "\$x=" . $this->calcu[$name] . ";";
				eval("$temp");
				$expr = $x . $calc;
				$calcu = "\$y=(" . $this->calcu[$name] . ")" . $calc . ";";
				if ($matches[1] != "+" && $matches[1] != "-")
					$this->calcu[$name] = "(" . $this->calcu[$name] . ")" . $calc;
				else
					$this->calcu[$name] .= $calc;
			}
			else
			{
				$calcu = "\$y=" . $calc . ";";
				$expr = $calc;
				$this->calcu[$name] = $expr;
			}
			eval("$calcu");
			if (! empty($result) || $result == 0)
				if ($this->bot->core("settings")->get("Calc", "ShowEquation"))
					return $expr . " = " . str_replace(".00", "", number_format($y, "2", ".", " "));
				else
					return $y;
			else
				return "Wrong syntax, please /tell <botname> <pre>help <pre>calc";
		}
		else
			return "Wrong syntax, please /tell <botname> <pre>help <pre>calc";
	}

	function show_calc($name)
	{
		if (isset($this->calcu[$name]))
		{
			$var = "\$calc = " . $this->calcu[$name] . ";";
			eval("$var");
			$return = $this->calcu[$name] . " = " . $calc;
			return $return;
		}
		else
			return "You've not made any calculations since my last restart.";
	}
}
?>
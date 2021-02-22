<?php
/*
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2020 Thomas Juberg Stens, ShadowRealm Creations and the BeBot development team.
*
*
* Module procs.php displays procs per profession. 
* author of this module mcgunman (RK1)
* input provide by 	Prostetnik(RK1)
* See Credits file for all acknowledgements.
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

$procs = new procs($bot);

class procs extends BaseActiveModule
{
	function __construct (&$bot)
	{
		parent::__construct($bot, get_class($this));
		$this -> register_command("all", "procs", "MEMBER");
		$this -> help['description'] = "Displays procs per profession.";
		$this -> help['command']['procs <expression>'] = "procs <profession>";
		$this -> help['notes'] = "You can use &lt;prof&gt; or &lt;profession&gt;.";
	}

	function command_handler($source, $msg, $type)
	{
		$this->error->reset();
		$com = $this->parse_com($msg, array('com', 'sub'));
		switch($com['com'])
		{
			case 'procs':
					if ($com['sub'] != "")
					{
                        if(($profname = $this -> bot -> core("professions") -> full_name($com['sub'])) instanceof BotError) return $profname;
                        return($this -> make_procs($source, $type, $com, $profname));
						break;
					}
					else
					{
						$this -> error -> set("You have to submit a name of one existing profession.");
						return($this->error->message());
						break;
					}
			break;
			default:
				$this -> error -> set("Broken plugin, received unhandled command: $command");
				return($this->error->message());
				break;
		}
	}
	/*
	This builts the output
	*/
	function make_procs($source, $type, $com, $profname)
	{
		$proflist['Adventurer']="<u>Adventurer Procs</u><br><br><div><img src=rdb://84789><br>Skin Protection - Shield 31 / Absorb 150 / 1 min<br>Machete Slice - Attacker Hit Health -137 ..-350 FireAC<br>Charring Blow - Attacker Hit Health -844..-1434 / -533..-1120 FireAC<br>Machete Flurry - Add Dmg +60 / 1 min<br>Aesir Absorbption - Defense modifier +50 / 30sec</div><div><img src=rdb://84310><br>Basic Dressing - Self Heal 15..25<br>Flaming Hit - Attacker Hit Health -12 ..-22 FireAC<br>Soothing Herbs - Self Heal 186 .. 391<br>Restore Vigor - Self Heal 356 .. 746<br>Durable Bandages - Team Heal 261 .. 595<br>Combustion - Attacker Hit Health -1294..-2415 FireAC<br>Healing Herbs - Self Heal 697 .. 1193</div>";
		$proflist['Agent']="<u>Agent Procs</u><br><br><div><img src=rdb://84789><br>Minor Nanobot Enhance - Damage Modifier +1 auf alle, 1 min<br>Intense Metabolism - NanoC.Init 250, 1 min<br>Grim Reaper - DOT 15 ticks at 4 seconds -136 Melee, = 1 min<br>Disable Cuffs - Reduce Snare and Root Duration by 3 hours<br>Weaken Threat - TauntNPC FightingTarget -4496</div><div><img src=rdb://84310><br>Alleviate Tension - Detaunt -500, 50 sec<br>Tainted Bullet - CriticalIncrease 15%, 15 sec<br>Cell Killer - DOT 6 ticks at 5 seconds -51 Melee, = 30 sec<br>Laming Shot - Damage Modifier +16 auf alle, 1 min<br>Sticky Shot - CriticalIncrease 22%, 15 sec<br>Laser Aim - CriticalIncrease 30%, 20 sec<br>Glue Shot - Damage Modifier +30 auf alle Arten, 1 min</div>";
		$proflist['Bureaucrat']="<u>Bureaucrat Procs</u><br><br><div><img src=rdb://84789><br>Wait In That Queue - FightingTarget Stun, 10sec<br>Lost Paperwork - FightingTarget Hit Health Melee -264 .. -532<br>Forms in Triplicate - Self Modify Nano delta +60<br>Tax Audit - FightingTarget Hit Health Energy -1375 .. -3211 & Taunt 3574<br>Tariffs - AOE 10m Root, 15sec</div><div><img src=rdb://84310><br>Inflation Adjustment - Self Modify Nano damage modifier +1%<br>Papercut - FightingTarget Hit Health Cold -10 .. -23<br>Deflation - Self Modify Nano damage modifier +3%<br>Next Window Over - Self Modify Nano delta +30<br>Social Services - FightingTarget Stun für 15 seconds<br>Wrong Window - Self Modify Nano damage modifier +4%<br>Please Hold - FightingTarget Modify Run speed -1500 </div>";
		$proflist['Doctor']="<u>Doctor Procs</u><br><br><div><img src=rdb://84789><br>Astringent - FightingTarget All Inits -1206 (Unstable)<br>Anesthetic - Team Heal 201..460<br>Muscular Malaise - FightingTarget All Inits -908 (Stable)<br>Healing Care - (DefProc 10%) Team Heal 434..820<br>Dangerous Culture - FightingTarget DoT PoisonAC -550 15hits @ 2s<br></div><div><img src=rdb://84310><br>Restrictive Bandaging - Self Heal 21-37<br>Inflammation - FightingTarget DoT PoisonAC -10 15hits @ 6s<br>Blood Transfusion - Self Heal 327-551<br>Pathogen - FightingTarget DoT PoisonAC -124 15hits @ 6s (Bugged, macht nur den ersten Nanohit)<br>Anatomic Blight - FightingTarget All Inits -2569 (Unstable)<br>Antiseptic - Self Heal 1133-1533<br>Massive Vitae Plan - Team Heal 485..970</div>";
		$proflist['Enforcer']="<u>Enforcer Procs</u><br><br><div><img src=rdb://84789><br>Vile Rage - self -492 health x 9 in SL, self -82 health x12 in RK, all inits +240, Runspeed +550, Nanoresist +420, Remove Combat nanos <= 31 NCU 8 times<br>Tear Ligament - dmg modifier +48, all off +170, hit target -1000 Runspeed (ranged weapons)<br>Inspire Rage - Taunt 600<br>Malicious Rage - all inits +350, Runspeed +800, Nanoresist +800<br>Raging Blow - dmg modifier +80, all off +252, hit target 3335 cold dmg (melee weapons)<br></div><div><img src=rdb://84310><br><br>Ignore Pain - max health +18, shield dmg +3, self -600 health (ranged weapons)<br>Bust Kneecaps - dmg modifier +12, all off +27, self -1000 runspeed (ranged weapons)<br>Shrug Off Hits - absorb 280 dmg<br>Unruly Screen - max health +228, shield dmg +22<br>Inspire Ire - Taunt 4750, Taunt 25 (melee weapons)<br>Violaton Buffer - max health +479, energy AC 240, shield dmg +60<br>Inspire Hate - Taunt 5865, Taunt 25 (melee weapons)</div>";
		$proflist['Engineer']="<u>Engineer Procs</u><br><br><div><img src=rdb://84789><br>Cushion Blows - Shield 1AC, 1 min<br>Splinter Preservation - Layered Absorb 375, 1 min<br>Endure Barrage - +332AC, 1 min<br>Energy Transfer - Shield 53AC, 1 min<br><br></div><div><img src=rdb://84310><br>Congenial Encasement - Reflect Shield 13%, 1 min<br>Destructive Signal - RangedInit. +80, 1 min<br>Drone Explosives - Proc-Dmg -497...-1016 Projectile<br>Destructive Theorem - RangedInit. +150, 1 min<br>Joint Discharge Array - Shield 39AC, 1 min<br>Drone Missiles - Proc-Dmg -1375...-3211 Energy<br>Assault Force Relief - +2500AC, 1 min</div>";
		$proflist['Fixer']="<u>Fixer Procs</u><br><br><div><img src=rdb://84789><br>Intense Metabolism - Nano Init 250<br>Escape The System - Roots -45sec<br>Dirty Tricks - Dodge Ranged 100<br>Backyard Bandages - Short Hot 362 - 370<br>Slip Them A Mickey - Damage 60</div><div><img src=rdb://84310><br>Contaminated Bullets - Damage 3<br>Underground Sutures - Short Hot 15 - 18<br>Fish in a Barrel - Evades -85<br>Fighting Chance - Damage 17<br>Bending the Rules - Damage 40<br>Luck's Calamity - Evades -170<br>Bootleg Remedies - Short Hot 406 - 439</div>";
		$proflist['Keeper']="<u>Keeper Procs</u><br><br><div><img src=rdb://84789><br>Eschew the Faithless - User Duck explosives/Dodge ranged 14 : Evade close 50, 1min<br>Affiliated Renewal - Self Hit Health 201 .. 460<br>Benevolent Barrier - Reflect damage +4, 10min<br>Symbiotic Bypass - User Duck explosives/Dodge ranged 40 : Evade close 140, 1min<br>Faithful Revival - Self Hit Health 485 .. 970</div><div><img src=rdb://84310><br>Righteous Strike - Add all Dmg +2, 1 min<br>Faithful Reconstruction - Self Hit Health 21 .. 42<br>Pure Strike - Add all Dmg +25, 1 min<br>Ignore the Unrepentant - User Duck explosives/Dodge ranged 30 : Evade close 110, 1min<br>Virtuous Reaper - Add all Dmg +40, 1 min<br>Ambient Purification - Self Hit Health 481 .. 948<br>Righteous Smite - Add all Dmg +95, 1 min</div>";
		$proflist['Martial Artist']="<u>Martial Artist Procs</u><br><br><div><img src=rdb://84789><br>Stinging Fist - + 19 auf alle Damagearten<br>Smashing Fist - + 63 auf alle Damagearten<br>Strengthen KI - + 676 MeeleeAC : +574 All AC : +40 STR<br>Absolute Fist - + 94 auf alle Damagearten</div><div><img src=rdb://84310><br>Medicinal Remedy -  Heilt 34-59 HP<br>Attack Ligaments - +2% CriticalIncrease<br>Strengthen Spirit - + 269 MeeleeAC : +229 All AC<br>Healing Meditation - Heilt 443-981 HP<br>Debilitating Strike - +19% CriticalIncrease<br>Disrupt KI - + 85 Evades<br>Self Reconstruction - Heilt 980-1803 HP</div>";
		$proflist['Meta-Physicist']="<u>Meta-Physicist Procs</u><br><br><div><img src=rdb://84789><br>Economic Nanobot Use - Self NPCostModifier -12%<br>Ego Strike - FightingTarget Hit ColdAC -602..-1268<br>Regain Focus - Self All Evades 108<br></div><div><img src=rdb://84310><br>Anticipated Evasion - Self All Evades 250<br>Diffuse Rage - FightingTarget All Damage -3 : FightingTarget All Inits -19 (Stable)<br>Mind Wail - FightingTarget Hit ColdAC -314..-699<br>Nanobot Contingent Arrest - FightingTarget All Nanoskills -2000<br>Sow Despair - FightingTarget Hit PoisonAC -12..-22<br>Sow Doubt - FightingTarget All Damage -18 : FightingTarget All Inits -134 (Stable)<br>Super-Ego Strike - FightingTarget Hit ColdAC -1500..-3000<br>Suppress Fury - FightingTarget All Damage -37 : FightingTarget All Inits -261 (Stable)<br>Thoughtful Means - Self NanoC.Init 200</div>";
		$proflist['Nano-Technician']="<u>Nano-Technician Procs</u><br><br><div><img src=rdb://84789><br>Circular Logic - nano 5 .. 5 120 hits, 15s delay<br>Source Tap - nano 102 .. 102 240 hits, 15s delay<br>Layered Amnesty -  Reflect Dmg 4<br>Thermal Reprieve - Reflect Dmg 10<br><br></div><div><img src=rdb://84310><br>Circular Logic - nano 5 .. 5 120 hits, 15s delay<br>Unstable Library - Max HP 27, All AC +17, NR +18<br>Increase Momentum - Nano init 200<br>Powered Nano Fortress - Max HP 246, All AC +167, NR +111<br>Looping Service - Nano cost modifier -22 <br>Harvest Energy - nano 235 .. 235 240 hits, 15s delay<br>Optimized Library - Max HP 331, All AC +331, NR +140<br>Accelerated Reality - Nano init 600</div>";
		$proflist['Shade']="<u>Shade Procs</u><br><br><div><img src=rdb://84789><br>Devious Spirit -  Extra-Hit Melee -13<br>Sap Life - HP-Drain: Target-Hit Energy -17, Heal 7<br>Elusive Spirit - Evade Buff: 1 min, Evade 56, Dodge/Duck 32<br>Drain Essence - HP-Drain: Target-Hit Energy -382, Heal 310<br>Twisted Caress - Extra-Hit Melee -550<br>Siphon Being - HP-Drain: Target-Hit Energy -580, Heal 577<br>Blackheart - Extra-Hit Melee -757</div><div><img src=rdb://84310><br>Misdirection - Evade Buff: 1 min, Evade 40, Dodge/Duck 25<br>Concealed Surprise - Extra-Hit Melee -134<br>Toxic Confusion - DOT Poison -46, 20 Treffer alle 4s<br>Shadowed Gift - DOT Poison -168, 15 Treffer alle 4s<br>Blackened Legacy - Evade Buff: 1 min, Evade 100, Dodge/Duck 50</div>";
		$proflist['Soldier']="<u>Soldier Procs</u><br><br><div><img src=rdb://84789><br>Concussive Shot - Self AddProjDmg 15 : Self AddEnergyDmg 15<br>Emergency Bandages - Self Max Health 176 : Self HealDelta 2<br>On The Double - Self AllInits 150<br>Furious Ammunition - Self AddProjDmg 88 : Self AddEnergyDmg 88<br><br></div><div><img src=rdb://84310><br>Shoot Artery - Self AddProjDmg 4 : Self AddEnergyDmg 4<br>Successful Targeting - Self AddAllOff 23<br>Deep Six Initiative - Self AllInits 50<br>Gear Assault Absorption - Self AllAC 280<br>Reconditioned - Self Max Health 361 : Self HealDelta 3<br>Target Acquired - Self AddAllOff 35<br>Graze Jugular Vein - Self AddProjDmg 70 : Self AddEnergyDmg 70<br>Fuse Body Armor - Self AllAC 800</div>";
		$proflist['Trader']="<u>Trader Procs</u><br><br><div><img src=rdb://84789><br>Rebate - FightingTarget Health -17 Energy AC  : Self Health +7<br>Unexpected Bonus - 	FightingTarget Health -247 EnergyAC : Self Health +172<br>Deplete Assets - Fighting Target all AC -449 : Self all AC +394<br>Unopened Letter - FightingTarget all AC -1098 : Self all AC +1067<br>Debt Collection - FightingTarget Health -(900-1000) EnergyAC : Self Health +(600-700)<br>Accumulated Interest - FightingTarget attack and nano skills -204 : Self attack and nano skills +204</div><div><img src=rdb://84310><br>Payment Plan - FightingTarget attack and nano skills -9 : Self attack and nano skills +9<br>Refinance Loans - FightingTarget all AC -200 : Self all AC +200<br>Escrow - Fighting Target 3 ticks at 15 seconds CurrentNano -13 : Self 3 ticks at 15 seconds CurrentNano +100<br>Unforgiven Debts - FightingTarget attack and nano skills-136 : Self attack and nano skills +136<br>Exchange Product - FightingTarget Health -467 EnergyAC : Self Health +423<br>Rigid Liquidation - FightingTarget 3 ticks at 15 seconds CurrentNano -307 : Self 3 ticks at 15 seconds CurrentNano +271</div>";
		$bonus = "\n\nSidenote : ##green##Defensive##end## procs have 10% chance to land when you're hit ; ##red##Offensive##end## procs have 5% chance to trigger when you hit.";
		$procs_output = '<a href="text://'.$proflist[$profname].$bonus.'">Display all procs for '.$profname.'</a>';
		$this -> bot -> send_output($source, $procs_output, $type);
	}
}
?>
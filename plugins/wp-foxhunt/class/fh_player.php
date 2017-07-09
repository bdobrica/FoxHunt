<?php
/**
 * Core of FH_*
 */

/**
 * Player
 *
 * @category
 * @package FoxHunt
 * @subpackage None
 * @copyright Core Security Advisers SRL
 * @author Bogdan Dobrica <bdobrica @ gmail.com>
 * @version 0.1
 *
 */
class FH_Player extends FH_Model {
	public static $version	= '1.0.0';

	public static $human	= 'Player';

	public static $scheme	= [];

	const GET		= 'player';
	const SHARE_INFO	= FALSE;
	const WARN_TIMER	= 30;
	const ALARM_ON		= 3;
	const ROUND3_MIN_SCORE	= 0;
	const ROUND3_MAX_SCORE	= 30;

	public static $T = 'fh_players';

	protected static $K = [
		'owner',
		'game',
		'name',
		'emails',
		'password',
		'timer',
		'state_data',
		'score',
		'state',
		'locale'
		];

	protected static $Q = [
		'`id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT',
		'`owner` int(11) NOT NULL DEFAULT 0',
		'`game` int(11) NOT NULL DEFAULT 0',
		'`name` varchar(64) NOT NULL DEFAULT \'\'',
		'`emails` text NOT NULL',
		'`password` varchar(32) NOT NULL DEFAULT \'\' UNIQUE',
		'`timer` text NOT NULL',
		'`state_data` text NOT NULL',
		'`score` int(11) NOT NULL DEFAULT 0',
		'`state` int(11) NOT NULL DEFAULT 0',
		'`locale` varchar(10) NOT NULL DEfAULT \'\''
		];

	public static $A = [
		'income'		=> /*T[*/'Income'/*]*/,
		'total_cost'		=> /*T[*/'Total Cost'/*]*/,
		'global_margin_rel'	=> /*T[*/'Global Margin (%)'/*]*/,
		'global_margin_abs'	=> /*T[*/'Global Margin'/*]*/,
		];

	public static $C = [
		'features',
		'warranty',
		'price',
		'delivery_term',
		'payment_term',
		'advertising_budget'
		];

	private $votes;
	private $market;
	private $computed;

	public function set ($key = null, $value = null) {
		if (is_string ($key) && (self::slug ($key) == 'state'))
			return parent::set ('state_data', serialize ($value));

		if (is_string ($key) && (self::slug ($key) == 'market')) {
			$this->market = is_array ($value) ? (isset ($value[$this->ID]) ? $value[$this->ID] : $value) : null;
			return TRUE;
			}

		if (is_string ($key) && (self::slug ($key) == 'computed')) {
			$this->computed = [];
			return TRUE;
			}

		if (is_string ($key) && (self::slug ($key) == 'negotiation_step')) {
			if (in_array ($value, [
				FH_Game::NEGOTIATION_0,
				FH_Game::NEGOTIATION_1,
				FH_Game::NEGOTIATION_2,
				FH_Game::NEGOTIATION_3
				])) {
				$all_states = $this->get ('state');
				$all_states[FH_Game::ROUND4_BEGIN]['step'] = $value;
				$this->set ('state', $all_states);
				}
			return TRUE;
			}

		if (is_string ($key) && (self::slug ($key) == 'timer')) {
			$time = time ();
			$game = new FH_Game ((int) $this->data['game']);
			$scenario = new FH_Scenario ($game->get ('scenario'));
			$state = $game->get ('state');
			if (!in_array ($state, FH_Game::$PL))
				return FALSE;

			if (is_null ($value)) {
				switch ($state) {
					case FH_Game::ROUND1_BEGIN:
						$timer = [FH_Game::ROUND1_BEGIN => [
							'lobby'	=> [ 'start' => $time, 'length' => 60 * $scenario->get ('1st_round_timer'), 'alarm' => 0, 'warn' => 0 ]
							]];
						break;
					case FH_Game::ROUND2_BEGIN:
						$timer = [FH_Game::ROUND2_BEGIN => [
							'quotations'	=> [ 'start' => $time, 'length' => 60 * $scenario->get ('offer_timer'), 'alarm' => 0, 'warn' => 0 ]
							]];
						break;
					case FH_Game::ROUND3_BEGIN:
						$timer = [FH_Game::ROUND3_BEGIN => [
							'presentation'	=> [ 'start' => $time, 'length' => 60 * $scenario->get ('presentation_timer'), 'alarm' => 0, 'warn' => 0 ]
							]];
						break;
					case FH_Game::ROUND4_BEGIN:
						$timer = [FH_Game::ROUND4_BEGIN => [
							'negotiation'	=> [ 'start' => $time, 'length' => 60 * $scenario->get ('negotiation_timer'), 'alarm' => 0, 'warn' => 0 ]
							]];
						break;
					}
				return parent::set ('timer', serialize ($timer));
				}
			else {
				$timer = unserialize ($this->data['timer']);
				$timer = $timer === FALSE ? [] : $timer;

				if ($value == 'clear')
					unset ($timer[$state]);
				else {
					switch ($state) {
						case FH_Game::ROUND1_BEGIN:
							if ($value == 'begin')
								$timer[FH_Game::ROUND1_BEGIN]['conversation'] = [ 'start' => $time, 'length' => 60 * $scenario->get ('conversation_timer') ];
							if ($value == 'end')
								unset ($timer[FH_Game::ROUND1_BEGIN]['conversation']);
							break;
						case FH_Game::ROUND2_BEGIN:
							break;
						case FH_Game::ROUND3_BEGIN:
							$timer[FH_Game::ROUND3_BEGIN]['presentation'] = [ 'start' => $time, 'length' => 60 * $scenario->get ('presentation_timer') ];
							break;
						case FH_Game::ROUND4_BEGIN:
							break;
						}
					}
				return parent::set ('timer', serialize ($timer));
				}
			return TRUE;
			}

		if (is_array ($key) && isset ($key['state'])) {
			$key['state_data'] = serialize ($key['state']);
			unset ($key['state']);
			}

		return parent::set ($key, $value);
		}

	public function get ($key = null, $opts = null) {
		if (is_string ($key)) {
			$slug = self::slug ($key);
			switch ($slug) {
				case 'emails_array':
					$out = [];
					$source = $this->data['emails'];
					if (!preg_match_all ('/[a-z][a-z0-9_.-]+@[a-z0-9.-]+\.[a-z]{2,4}/', strtolower ($source), $matches))
						return $out;
					foreach ($matches[0] as $email)
						$out[] = $email;
					return $out;
					break;
				case 'state':
					$out = unserialize ($this->get ('state_data'));
					if ($out === FALSE)
						$out = [];

					if (!empty (FH_Game::$PL))
						foreach (FH_Game::$PL as $state_id)
							if (!isset ($out[$state_id]))
								$out[$state_id] = [
									'in_progress'	=> FALSE,
									'submitted'	=> FALSE,
									'timeout'	=> FALSE,
									'data'		=> []
									];
					return $out;
					break;
				case 'timer':
					$time = time ();
					$game = new FH_Game ((int) $this->data['game']);
					$state = $game->get ('state');
					if (!in_array ($state, FH_Game::$PL))
						return FALSE;
					
					$timer = unserialize ($this->data['timer']);
					if (!isset ($timer[$state]))
						return FALSE;

					$timer = $timer[$state];
					foreach ($timer as $scope => $data) {
						$seconds = $time - $data['start'];
						if ($seconds > $data['length']) $seconds = $data['length'];
						$sec = $seconds % 60;
						$min = intval ($seconds / 60);
						$timer[$scope]['time'] = sprintf ('%02d:%02d', $min, $sec);

						$seconds = $data['start'] + $data['length'] - $time;
						if ($seconds < 0) $seconds = 0;
						$sec = $seconds % 60;
						$min = intval ($seconds / 60);
						$timer[$scope]['down'] = sprintf ('%02d:%02d', $min, $sec);

						$timer[$scope]['alarm'] = $time > ($data['start'] + $data['length']) ? 1 : 0;
						$timer[$scope]['warn'] = $time > ($data['start'] + $data['length'] - self::WARN_TIMER) ? 1 : 0;

						/*
						if ($state == FH_Game::ROUND2_BEGIN)
							$timer[$scope]['alarm'] = $data['start'] + $data['length'] - $time < 2 ? 1 : 0;
						else
							$timer[$scope]['alarm'] = $time > ($data['start'] + $data['length']) ? 1 : ($scope == 'conversation' ? 1 : 0);
						*/

						//$timer[$scope]['alarm'] = 1;
						//$timer[$scope]['alarm'] = $time > ($data['start'] + $data['length']) ? 1 : 0;
						}

					return $timer;
					break;
				case 'votes':
					try {
						$game = new FH_Game ($this->data['game']);
						}
					catch (FH_Exception $exception) {
						return FALSE;
						}

					$players = new FH_List ('FH_Player', [sprintf ('game=%d', $game->get ())]);
					if ($players->is ('empty')) return FALSE;

					$polls = new FH_List ('FH_Poll', $game->scenario ('path'));
					if ($polls->is ('empty')) return FALSE;

					$votes = [];
					foreach ($polls->get () as $poll) $votes[$poll->get ()] = 0;

					$count = 0;
					foreach ($players->get () as $player) {
						$all_states = $player->get ('state');
						$state = $all_states[FH_Game::ROUND3_BEGIN];
						if (!isset ($state['data']['voted'][$this->ID]) || empty($state['data']['voted'][$this->ID])) continue;
						foreach ($state['data']['voted'][$this->ID] as $sid => $data) {
							$count ++;
							foreach ($votes as $key => $value) {
								if (isset ($data[$key]))
									$votes[$key] += $data[$key];
								}
							}
						}

					if ($count > 0)
						foreach ($votes as $key => $value)
							$votes[$key] = sprintf ('%.2f', $value / $count);

					return is_null ($opts) ? $votes : $votes[$opts];
					break;
				case 'votes_score':
					try {
						$game = new FH_Game ($this->data['game']);
						}
					catch (FH_Exception $exception) {
						return FALSE;
						}

					$players = new FH_List ('FH_Player', [sprintf ('game=%d', $game->get ())]);
					if ($players->is ('empty')) return FALSE;

					$polls = new FH_List ('FH_Poll', $game->scenario ('path'));
					if ($polls->is ('empty')) return FALSE;

					$votes = [];
					foreach ($players->get () as $player)
						$votes[$player->get()] = 0;

					$count = 0;
					foreach ($players->get () as $player) {
						$all_states = $player->get ('state');
						$state = $all_states[FH_Game::ROUND3_BEGIN];
						if (!isset ($state['data']['voted']) || empty($state['data']['voted'])) continue;
						
						foreach ($state['data']['voted'] as $player_id => $vote_data) {
							foreach ($vote_data as $sid => $data) {
								foreach ($polls->get () as $poll) {
									$votes[$player_id] += isset ($data[$poll->get ()]) ? $data[$poll->get ()] : 0;
									}
								}
							}
						}

					$min = current ($votes);
					$max = current ($votes);
					reset ($votes);
					foreach ($votes as $vote) {
						$min = $vote < $min ? $vote : $min;
						$max = $vote > $max ? $vote : $max;
						}
					
					if ($min == $max) return 0;
					return floor (self::ROUND3_MIN_SCORE + (self::ROUND3_MAX_SCORE - self::ROUND3_MIN_SCORE) * ($votes[$this->ID] - $min) / ($max - $min));
					break;
				case 'score':
					$all_states = $this->get ('state');
					$game = new FH_Game ((int) $this->data['game']);
					$score_override = $game->get ('scores');

					$questions = isset ($all_states[FH_Game::ROUND1_BEGIN]['data']['questions']) ? $all_states[FH_Game::ROUND1_BEGIN]['data']['questions'] : [];
					$round1_score = 0;

					$characters_score = [];

					if (!empty ($questions))
						foreach ($questions as $character_id => $character_q) {
							if (empty ($character_q)) continue;

							$character = new FH_Character ($game->scenario ('path'), $character_id);
							$conversation = new FH_Conversation ($character);

							foreach ($character_q as $question) {
								$answer = $conversation->get ('answer', $question);
								$q_score = isset ($answer->player_score) ? $answer->player_score : 0;

								$round1_score += $q_score;

								if (!isset ($characters_score[$character_id]))
									$characters_score[$character_id] = 0;

								$characters_score[$character_id] += $q_score;
								}
							}

					if (isset ($score_override[FH_Game::ROUND1_END][$this->ID])) {
						$round1_score = $score_override[FH_Game::ROUND1_END][$this->ID]['total'];
						foreach ($characters_score as $character_id => $character_score) {
							$character = new FH_Character ($game->scenario ('path'), $character_id);
							$characters_score[$character_id] = $score_override[FH_Game::ROUND1_END][$this->ID][$character->get ('name')];
							}
						}

					if (is_string ($opts) && $opts == 'round1') return $round1_score;
					if (is_string ($opts) && $opts == 'characters') return $characters_score;

					$round2_score = 0;
					$products = new FH_List ('FH_Product', $game->scenario ('path'));
					if (!$products->is ('empty')) {
						$total_offset = 0;
						foreach ($products->get () as $product) {
							$qualities = $product->get ('quality');

							$perceived_cost = $this->compute ($product, 'rtwo_perceived_cost');
							$desired_value = $this->compute ($product, 'rtwo_desired_value');

							if (!empty ($qualities))
								foreach ($qualities as $quality_slug => $quality_data) {
									$quality_offset = $quality_data['desired_price'] * $quality_data['desired_quantity'];

									$round2_score += $quality_offset * ($quality_data['desired_price'] ? (100 * abs ($perceived_cost[$quality_slug] / $desired_value[$quality_slug] - 1)) : 25.00);

									$total_offset += $quality_offset;
									}
							}
						$round2_score /= $total_offset;
						$round2_score = 25 - $round2_score;
						$round2_score = $round2_score < 0 ? 0 : $round2_score;
						$round2_score = $round2_score > 20 ? 20 : $round2_score;
						$round2_score = floor ($round2_score);
						}

					if (is_string ($opts) && $opts == 'round2') return $round2_score;

					$round3_score = $this->get ('votes_score');
					if (isset ($score_override[FH_Game::ROUND3_END][$this->ID]))
						$round3_score = $score_override[FH_Game::ROUND3_END][$this->ID];
					if (is_string ($opts) && $opts == 'round3') return $round3_score;

					$score = $round1_score + $round2_score + $round3_score;

					return $score;
					return $this->data['score'];
					break;
				case 'enable_purchase':
					$all_states = $this->get ('state');
					$game = new FH_Game ((int) $this->data['game']);

					$questions = isset ($all_states[FH_Game::ROUND1_BEGIN]['data']['questions']) ? $all_states[FH_Game::ROUND1_BEGIN]['data']['questions'] : [];
					$enable_purchase = $game->scenario ('enable_purchase') ? TRUE : FALSE;

					if ($enable_purchase) return TRUE;

					if (!empty ($questions))
						foreach ($questions as $character_id => $character_q) {
							if (empty ($character_q)) continue;

							$character = new FH_Character ($game->scenario ('path'), $character_id);
							$conversation = new FH_Conversation ($character);

							foreach ($character_q as $question) {
								$answer = $conversation->get ('answer', $question);
								if (isset ($answer->allow_purchase) && $answer->allow_purchase) {
									$enable_purchase = TRUE;
									break;
									}
								}

							if ($enable_purchase)
								break;
							}

					return $enable_purchase;
					break;
				case 'quotations':
/*
		'quantity'		=> FH_Theme::r ('quantity'),
		'features'		=> FH_Theme::r ('features'),
		'location'		=> FH_Theme::r ('location'),
		'delivery_term'		=> FH_Theme::r ('delivery_term'),
		'price'			=> FH_Theme::r ('price'),
		'advertising_budget'	=> FH_Theme::r ('advertising_budget'),
		'payment_term'		=> FH_Theme::r ('payment_term'),
		'warranty'		=> FH_Theme::r ('warranty')
*/
					$all_states = $this->get ('state');
					$quotations = [];

					$negotiation_step = isset ($all_states[FH_Game::ROUND4_BEGIN]['step']) ? $all_states[FH_Game::ROUND4_BEGIN]['step'] : FH_Game::NEGOTIATION_0;

					switch ($negotiation_step) {
						case FH_Game::NEGOTIATION_0:
							$quotations = isset ($all_states[FH_Game::ROUND2_BEGIN]['data']['quotations']) ? $all_states[FH_Game::ROUND2_BEGIN]['data']['quotations'] : [];
							if (!is_null ($opts) && isset ($all_states[FH_Game::ROUND4_BEGIN]['data']['negotiation'][FH_Game::NEGOTIATION_1]))
								$quotations = $all_states[FH_Game::ROUND4_BEGIN]['data']['negotiation'][FH_Game::NEGOTIATION_1];
							break;
						case FH_Game::NEGOTIATION_1:
							$quotations = $all_states[FH_Game::ROUND4_BEGIN]['data']['negotiation'][FH_Game::NEGOTIATION_1];
							if (!is_null ($opts) && isset ($all_states[FH_Game::ROUND4_BEGIN]['data']['negotiation'][FH_Game::NEGOTIATION_2]))
								$quotations = $all_states[FH_Game::ROUND4_BEGIN]['data']['negotiation'][FH_Game::NEGOTIATION_2];
							break;
						case FH_Game::NEGOTIATION_2:
							$quotations = $all_states[FH_Game::ROUND4_BEGIN]['data']['negotiation'][FH_Game::NEGOTIATION_2];
							if (!is_null ($opts) && isset ($all_states[FH_Game::ROUND4_BEGIN]['data']['negotiation'][FH_Game::NEGOTIATION_3]))
								$quotations = $all_states[FH_Game::ROUND4_BEGIN]['data']['negotiation'][FH_Game::NEGOTIATION_3];
							break;
						case FH_Game::NEGOTIATION_3:
							$quotations = $all_states[FH_Game::ROUND4_BEGIN]['data']['negotiation'][FH_Game::NEGOTIATION_3];
							break;
						}

					return $quotations;
					break;
				case 'rtwo_quotations':
					$all_states = $this->get ('state');
					return isset ($all_states[FH_Game::ROUND2_BEGIN]['data']['quotations']) ? $all_states[FH_Game::ROUND2_BEGIN]['data']['quotations'] : [];
					break;
				case 'processed_quotations':
					$quotations = is_null ($opts) ? $this->get ('quotations') : $opts;
					$processed = [];

					$game = new FH_Game ((int) $this->data['game']);
					$products = new FH_List ('FH_Product', $game->scenario ('path'));
					$warranties = new FH_List ('FH_Warranty', $game->scenario ('path'));
					$locations = new FH_List ('FH_Location', $game->scenario ('path'));

					if (!$products->is ('empty'))
						foreach ($products->get () as $product) {
							$qualities = $product->get ('quality');
							if (empty ($qualities)) continue;

							foreach ($qualities as $quality_slug => $quality_data) {
								$processed[$product->get ()][$quality_slug] = [
									'quantity'		=> 0,
									'features'		=> [],
									'delivery_term'		=> 0,
									'price'			=> 0,
									'advertising_budget'	=> 0,
									'payment_term'		=> 0,
									'warranties'		=> [],
									'parameters'		=> [
										'max_price_accepted'		=> $quality_data['max_price_accepted'],
										'desired_price'			=> $quality_data['desired_price'],

										'max_delivery_time'		=> 0,
										'desired_delivery_time'		=> 0,

										'min_payment_term_accepted'	=> $quality_data['min_paym_term_accepted'],
										'desired_payment_term'		=> $quality_data['desired_payment_term'],

										'min_adv_budget_accepted'	=> $quality_data['min_adv_budget_accepted'],
										'desired_adv_budget'		=> $quality_data['desired_adv_budget'],

										'aggressiveness'		=> $game->scenario ('aggressiveness'),
										'score_weight'			=> $game->scenario ('score_weight'),
										'sweetener'			=> $game->scenario ('sweetener')
										]
									];
								}
							}

					foreach ($quotations as $quotation) {
						$processed[$quotation['product']][$quotation['quality']]['quantity'] += $quotation['quantity'];

						$processed[$quotation['product']][$quotation['quality']]['features'] = array_unique (array_merge (
							$processed[$quotation['product']][$quotation['quality']]['features'],
							isset ($quotation['features']) ? $quotation['features'] : []
							));

						if (isset ($quotation['location'])) {
							$location = new FH_Location ($game->scenario ('path'), $quotation['location']);
							$processed[$quotation['product']][$quotation['quality']]['parameters']['max_delivery_time'] += $location->get ('max_delivery_time') * $quotation['quantity'];
							$processed[$quotation['product']][$quotation['quality']]['parameters']['desired_delivery_time'] += $location->get ('desired_delivery_time') * $quotation['quantity'];
							}

						if (isset ($quotation['delivery_term']))
							$processed[$quotation['product']][$quotation['quality']]['delivery_term'] += $quotation['delivery_term'] * $quotation['quantity'];

						if (isset ($quotation['price']))
							$processed[$quotation['product']][$quotation['quality']]['price'] += $quotation['price'] * $quotation['quantity'];

						if (isset ($quotation['advertising_budget']))
							$processed[$quotation['product']][$quotation['quality']]['advertising_budget'] += $quotation['advertising_budget'] * $quotation['quantity'];

						if (isset ($quotation['payment_term']))
							$processed[$quotation['product']][$quotation['quality']]['payment_term'] += $quotation['payment_term'] * $quotation['quantity'];

						if (isset ($quotation['warranty']) && ($quotation['warranty'] != 'none') && !in_array ($quotation['warranty'], $processed[$quotation['product']][$quotation['quality']]['warranties']))
							$processed[$quotation['product']][$quotation['quality']]['warranties'][] = $quotation['warranty'];
						}

					if (!empty ($processed))
						foreach ($processed as $product_slug => $product_quotation) {
							if (!empty ($product_quotation))
								foreach ($product_quotation as $quality_slug => $quality_quotation) {
									$quantity = $quality_quotation['quantity'];

									if ($quantity == 0) {
										}
									else {
										$processed[$product_slug][$quality_slug]['parameters']['max_delivery_time'] /= $quantity;
										$processed[$product_slug][$quality_slug]['parameters']['desired_delivery_time'] /= $quantity;

										$processed[$product_slug][$quality_slug]['delivery_term'] /= $quantity;
										$processed[$product_slug][$quality_slug]['price'] /= $quantity;
										$processed[$product_slug][$quality_slug]['advertising_budget'] /= $quantity;
										$processed[$product_slug][$quality_slug]['payment_term'] /= $quantity;
										}
									}
							}
					return $processed;
					break;
				case 'rtwo_processed_quotations':
					$quotations = $this->get ('rtwo_quotations');
					return $this->get ('processed_quotations', $quotations);
					break;
				case 'counter':
					$all_states = $this->get ('state');
					$counter = [];
					if (isset ($all_states[FH_Game::ROUND4_BEGIN]['in_progress']) && $all_states[FH_Game::ROUND4_BEGIN]['in_progress']) {
						$negotiation_step = $this->get ('negotiation_step');

						if ($negotiation_step == FH_Game::NEGOTIATION_1)
							$counter = $all_states[FH_Game::ROUND4_BEGIN]['data']['counter'][FH_Game::NEGOTIATION_1];
						else
						if ($negotiation_step == FH_Game::NEGOTIATION_2)
							$counter = $all_states[FH_Game::ROUND4_BEGIN]['data']['counter'][FH_Game::NEGOTIATION_2];
						}
					return is_null ($opts) ? $counter : $counter[$opts];
					break;
				case 'best':
					$all_states = $this->get ('state');
					$best = [];

					if (isset ($all_states[FH_Game::ROUND2_BEGIN]['data']['quotations']))
						$processed = $this->get ('processed_quotations', $all_states[FH_Game::ROUND2_BEGIN]['data']['quotations']);

					if (!empty ($processed))
						foreach ($processed as $product_slug => $product_data) {
							if (!empty ($product_data))
								foreach ($product_data as $quality_slug => $quality_data)
									$best[$product_slug][$quality_slug] = [
										'price'			=> [ $quality_data['price'] ],
										'delivery_term'		=> [ $quality_data['delivery_term'] ],
										'payment_term'		=> [ $quality_data['payment_term'] ],
										'advertising_budget'	=> [ $quality_data['advertising_budget'] ],
										];
							}

					if (empty ($best))
						return FALSE;

					if (isset ($all_states[FH_Game::ROUND4_BEGIN]['step']))
						switch ($all_states[FH_Game::ROUND4_BEGIN]['step']) {
							case FH_Game::NEGOTIATION_3:
							case FH_Game::NEGOTIATION_2:
								$processed = $this->get ('processed_quotations', $all_states[FH_Game::ROUND4_BEGIN]['data']['negotiation'][FH_Game::NEGOTIATION_2]);
								if (!empty ($processed))
									foreach ($processed as $product_slug => $product_data) {
										if (!empty ($product_data))
											foreach ($product_data as $quality_slug => $quality_data) {
												$best[$product_slug][$quality_slug]['price'][]			= $quality_data['price'];
												$best[$product_slug][$quality_slug]['delivery_term'][]		= $quality_data['delivery_term'];
												$best[$product_slug][$quality_slug]['payment_term'][]		= $quality_data['payment_term'];
												$best[$product_slug][$quality_slug]['advertising_budget'][]	= $quality_data['advertising_budget'];
												}
										}
							case FH_Game::NEGOTIATION_1:
								$processed = $this->get ('processed_quotations', $all_states[FH_Game::ROUND4_BEGIN]['data']['negotiation'][FH_Game::NEGOTIATION_1]);
								if (!empty ($processed))
									foreach ($processed as $product_slug => $product_data) {
										if (!empty ($product_data))
											foreach ($product_data as $quality_slug => $quality_data) {
												$best[$product_slug][$quality_slug]['price'][]			= $quality_data['price'];
												$best[$product_slug][$quality_slug]['delivery_term'][]		= $quality_data['delivery_term'];
												$best[$product_slug][$quality_slug]['payment_term'][]		= $quality_data['payment_term'];
												$best[$product_slug][$quality_slug]['advertising_budget'][]	= $quality_data['advertising_budget'];
												}
										}
								break;
							}

					foreach ($best as $product_slug => $product_best) {
						if (!empty ($product_best))
							foreach ($product_best as $quality_slug => $quality_best) {
								$best[$product_slug][$quality_slug] = [
									'price'			=> min ($quality_best['price']),
									'delivery_term'		=> min ($quality_best['delivery_term']),
									'payment_term'		=> max ($quality_best['payment_term']),
									'advertising_budget'	=> max ($quality_best['advertising_budget'])
									];
								}
						}

					return is_null ($opts) ? $best : $best[$opts];
					break;
				case 'counter_offer':
					$counter_offer = [];
					foreach (self::$C as $key) {
						$counter_offer[$key] = $this->compute ('counter_' . $key);
						}
					return $counter_offer;
					break;
				case 'negotiation_step':
					$all_states = $this->get ('state');

					return isset ($all_states[FH_Game::ROUND4_BEGIN]['step']) ? $all_states[FH_Game::ROUND4_BEGIN]['step'] : FH_Game::NEGOTIATION_0;
					break;
				}
			}
		return parent::get ($key, $opts);
		}

	public function login ($password = '') {
		global $wpdb;

		$sql = $wpdb->prepare ('select * from `' . $wpdb->prefix . static::$T . '` where password=%s', $password);
		$data = $wpdb->get_row ($sql, ARRAY_A);

		if (empty ($data))
			return FALSE;

		$this->ID = (int) $data['id'];
		unset ($data['id']);
		$this->data = $data;

		$storage = new FH_Storage ();
		$storage->set ('player', $this->ID);

		return TRUE;
		}

	public function is () {
		return $this->ID > 0;
		}

	public function compute ($key = null, $opts = null) {
		if (is_object ($key) && ($key instanceof FH_Product)) {
			if (isset ($this->computed[$key->get ()][$opts]))
				return $this->computed[$key->get ()][$opts];

			$qualities = $key->get ('quality');
			$all_states = unserialize ($this->data['state_data']);
			$game = new FH_Game ((int) $this->data['game']);
			$scenario = new FH_Scenario ($game->get ('scenario'));

			$computed = [];
			foreach ($qualities as $quality_slug => $quality_data)
				$computed[$quality_slug] = 0;

			switch ($opts) {
				case 'units_available':
					$state = $all_states[FH_Game::ROUND2_BEGIN]['data'];
					$units_quoted = $this->compute ($key, 'units_quoted');

					foreach ($computed as $quality_slug => $value)
						$computed[$quality_slug] = $qualities[$quality_slug]['quantity'] + (isset ($state['acquired'][$key->get ()][$quality_slug]) ? $state['acquired'][$key->get ()][$quality_slug] : 0) - $units_quoted[$quality_slug];
					break;
				case 'purchasable_quantity':
					$state = $all_states[FH_Game::ROUND2_BEGIN]['data'];

					foreach ($computed as $quality_slug => $value)
						$computed[$quality_slug] = $qualities[$quality_slug]['purchasable_quantity'] - (isset ($state['acquired'][$key->get ()][$quality_slug]) ? $state['acquired'][$key->get ()][$quality_slug] : 0);
					break;
				case 'units_quoted':
					/***#***/
					if (isset ($all_states[FH_Game::ROUND4_BEGIN]['submitted']) && isset ($all_states[FH_Game::ROUND4_BEGIN]['data']['negotiation'][FH_Game::NEGOTIATION_3]))
						$quotations = $all_states[FH_Game::ROUND4_BEGIN]['data']['negotiation'][FH_Game::NEGOTIATION_3];
					else
						$quotations = isset ($all_states[FH_Game::ROUND2_BEGIN]['data']['quotations']) ? $all_states[FH_Game::ROUND2_BEGIN]['data']['quotations'] : [];

					if (is_null ($this->market)) {
						foreach ($computed as $quality_slug => $value)
							if (!empty ($quotations))
								foreach ($quotations as $quotation_id => $quotation_data)
									if ($quotation_data['product'] == $key-> get() && $quotation_data['quality'] == $quality_slug)
										$computed[$quality_slug] = isset ($computed[$quality_slug]) ? $computed[$quality_slug] + $quotation_data['quantity'] : $quotation_data['quantity'];
						}
					else {
						foreach ($computed as $quality_slug => $value)
							if (isset ($this->market[$key->get ()][$quality_slug]))
								$computed[$quality_slug] = floor ($this->market[$key->get ()][$quality_slug]);
						}
					break;
				case 'average_unit_cost':
					$state = isset ($all_states[FH_Game::ROUND2_BEGIN]['data']) ? $all_states[FH_Game::ROUND2_BEGIN]['data'] : [];

					$quoted = [];
					if (!is_null ($this->market))
						$quoted = $this->market[$key->get ()];
					else {
						foreach ($computed as $quality_slug => $value) {
							$quoted[$quality_slug] = 0;
							if (!empty ($state['quotations']))
								foreach ($state['quotations'] as $quotation_id => $quotation_data)
									if ($quotation_data['product'] == $key->get () && $quotation_data['quality'] == $quality_slug)
										$quoted[$quality_slug] = $quoted[$quality_slug] + $quotation_data['quantity'];
							}
						}

					$available = [];
					foreach ($computed as $quality_slug => $value)
						$available[$quality_slug] = $qualities[$quality_slug]['quantity'] < $quoted[$quality_slug] ? $qualities[$quality_slug]['quantity'] : $quoted[$quality_slug];

					$acquired = [];
					foreach ($computed as $quality_slug => $value)
						$acquired[$quality_slug] = $quoted[$quality_slug] - $qualities[$quality_slug]['quantity'] > 0 ? $quoted[$quality_slug] - $qualities[$quality_slug]['quantity'] : 0;
					
					foreach ($computed as $quality_slug => $value)
						$computed[$quality_slug] = $quoted[$quality_slug] ? sprintf ('%.2f', ($available[$quality_slug] * $qualities[$quality_slug]['unit_cost'] + $acquired[$quality_slug] * $qualities[$quality_slug]['purchased_unit_cost']) / $quoted[$quality_slug]) : FH_Theme::__ (/*T[*/'N/A'/*]*/);
					break;
				case 'rtwo_average_features_cost':
				case 'average_features_cost':
					/***#***/
					if ($opts == 'rtwo_average_features_cost')
						$quotations = isset ($all_states[FH_Game::ROUND2_BEGIN]['data']['quotations']) ? $all_states[FH_Game::ROUND2_BEGIN]['data']['quotations'] : [];
					else {
						if (isset ($all_states[FH_Game::ROUND4_BEGIN]['submitted']) && isset ($all_states[FH_Game::ROUND4_BEGIN]['data']['negotiation'][FH_Game::NEGOTIATION_3]))
							$quotations = $all_states[FH_Game::ROUND4_BEGIN]['data']['negotiation'][FH_Game::NEGOTIATION_3];
						else
							$quotations = isset ($all_states[FH_Game::ROUND2_BEGIN]['data']['quotations']) ? $all_states[FH_Game::ROUND2_BEGIN]['data']['quotations'] : [];
						}

					$average_unit_cost = $this->compute ($key, 'average_unit_cost');

					foreach ($computed as $quality_slug => $value) {
						if (!empty ($quotations))
							foreach ($quotations as $quotation_id => $quotation_data)
								if ($quotation_data['product'] == $key-> get() && $quotation_data['quality'] == $quality_slug) {
									if (!empty ($quotation_data['features']) && is_array ($quotation_data['features'])) {
										foreach ($quotation_data['features'] as $feature_id) {
											$feature = new FH_Feature ($key->get ('path'), $feature_id);
											$computed[$quality_slug] += 0.01 * $feature->get ('cost') * 
												$average_unit_cost[$quality_slug];
												//$qualities[$quality_slug]['unit_cost'];
											}
										}
									}
						}

					foreach ($computed as $quality_slug => $value)
						$computed[$quality_slug] = sprintf ('%.2f', $value);
					break;
				case 'rtwo_desired_features_cost':
					$features = new FH_List ('FH_Feature', $game->scenario ('path'));
					foreach ($computed as $quality_slug => $value) {
						foreach ($features->get () as $feature) {
							if ($feature->get ('mandatory'))
								$computed[$quality_slug] += 0.01 * $feature->get ('cost') * $qualities[$quality_slug]['desired_price'];
							}
						}

					foreach ($computed as $quality_slug => $value)
						$computed[$quality_slug] = sprintf ('%.2f', $value);
					break;
				case 'desired_features_cost':
					$desired_features = $this->compute ('counter_features');
					$features = new FH_List ('FH_Feature', $game->scenario ('path'));
					foreach ($computed as $quality_slug => $value) {
						if (!$features->is ('empty'))
							foreach ($features->get () as $feature) {
								if (in_array ($feature->get (), $desired_features[$key->get ()][$quality_slug]))
									$computed[$quality_slug] += 0.01 * $feature->get ('cost') * $qualities[$quality_slug]['desired_price'];
								}
						}

					foreach ($computed as $quality_slug => $value)
						$computed[$quality_slug] = sprintf ('%.2f', $value);
					break;

				case 'rtwo_desired_delivery_cost':
				case 'rtwo_average_delivery_cost':
				case 'average_delivery_cost':
				case 'desired_delivery_cost':
					/***#***/
					if ($opts == 'average_delivery_cost') {
						if (isset ($all_states[FH_Game::ROUND4_BEGIN]['submitted']) && isset ($all_states[FH_Game::ROUND4_BEGIN]['data']['negotiation'][FH_Game::NEGOTIATION_3]))
							$quotations = $all_states[FH_Game::ROUND4_BEGIN]['data']['negotiation'][FH_Game::NEGOTIATION_3];
						else
							$quotations = isset ($all_states[FH_Game::ROUND2_BEGIN]['data']['quotations']) ? $all_states[FH_Game::ROUND2_BEGIN]['data']['quotations'] : [];
						}
					else
						$quotations = isset ($all_states[FH_Game::ROUND2_BEGIN]['data']['quotations']) ? $all_states[FH_Game::ROUND2_BEGIN]['data']['quotations'] : [];

					if ($opts == 'rtwo_desired_delivery_cost')
						$average_unit_cost = $this->compute ($key, 'desired_unit_price');
					else
						$average_unit_cost = $this->compute ($key, 'average_unit_cost');

					foreach ($computed as $quality_slug => $value) {
						if (!empty ($quotations))
							foreach ($quotations as $quotation_id => $quotation_data)
								if ($quotation_data['product'] == $key-> get() && $quotation_data['quality'] == $quality_slug) {
									if (isset ($quotation_data['location'])) {
										$location = new FH_Location ($key->get ('path'), $quotation_data['location']);
										$computed[$quality_slug] +=
										0.01 * $location->get ('delivery_cost') * 
											// $qualities[$quality_slug]['unit_cost'] + 
											$average_unit_cost[$quality_slug] +
										(($location->get ('delivery_time') - $quotation_data['delivery_term']) > 0 ?
											0.01 * ($location->get ('delivery_time') - $quotation_data['delivery_term']) * $location->get ('day_saved_cost') *
												// $qualities[$quality_slug]['unit_cost'] :
												$average_unit_cost[$quality_slug] :
											0);
										}
									}
						}

					foreach ($computed as $quality_slug => $value)
						$computed[$quality_slug] = sprintf ('%.2f', $value);
					break;
				case 'rtwo_average_promotion_cost':
				case 'average_promotion_cost':
					/***#***/
					if ($opts == 'rtwo_average_promotion_cost')
						$quotations = isset ($all_states[FH_Game::ROUND2_BEGIN]['data']['quotations']) ? $all_states[FH_Game::ROUND2_BEGIN]['data']['quotations'] : [];
					else {
						if (isset ($all_states[FH_Game::ROUND4_BEGIN]['submitted']) && isset ($all_states[FH_Game::ROUND4_BEGIN]['data']['negotiation'][FH_Game::NEGOTIATION_3]))
							$quotations = $all_states[FH_Game::ROUND4_BEGIN]['data']['negotiation'][FH_Game::NEGOTIATION_3];
						else
							$quotations = isset ($all_states[FH_Game::ROUND2_BEGIN]['data']['quotations']) ? $all_states[FH_Game::ROUND2_BEGIN]['data']['quotations'] : [];
						}

					$quoted = [];
					$advertising = [];
					foreach ($computed as $quality_slug => $value) {
						$quoted[$quality_slug] = 0;
						$advertising[$quality_slug] = 0;
						if (!empty ($quotations))
							foreach ($quotations as $quotation_id => $quotation_data)
								if ($quotation_data['product'] == $key->get () && $quotation_data['quality'] == $quality_slug) {
									$quoted[$quality_slug] = $quoted[$quality_slug] + $quotation_data['quantity'];
									$advertising[$quality_slug] = $advertising[$quality_slug] + isset ($quotation_data['advertising_budget']) ? $quotation_data['advertising_budget'] : 0;
									}
						}

					if (!is_null ($this->market))
						$quoted = $this->market[$key->get ()];

					$available = [];
					foreach ($computed as $quality_slug => $value)
						$computed[$quality_slug] = $quoted[$quality_slug] ? sprintf ('%.2f', $advertising[$quality_slug] / $quoted[$quality_slug]) : 'N/A';
					break;
				case 'rtwo_desired_promotion_cost':
				case 'desired_promotion_cost':
					foreach ($computed as $quality_slug => $value)
						// $computed[$quality_slug] = $qualities[$quality_slug]['desired_adv_budget'] / $qualities[$quality_slug]['desired_quantity'];
						$computed[$quality_slug] = $qualities[$quality_slug]['desired_adv_budget'] / ($qualities[$quality_slug]['quantity'] + $qualities[$quality_slug]['purchasable_quantity']);

					foreach ($computed as $quality_slug => $value)
						$computed[$quality_slug] = sprintf ('%.2f', $value);
					break;
				case 'rtwo_average_financing_cost':
				case 'average_financing_cost':
					/***#***/
					if ($opts == 'rtwo_average_financing_cost')
						$quotations = isset ($all_states[FH_Game::ROUND2_BEGIN]['data']['quotations']) ? $all_states[FH_Game::ROUND2_BEGIN]['data']['quotations'] : [];
					else {
						if (isset ($all_states[FH_Game::ROUND4_BEGIN]['submitted']) && isset ($all_states[FH_Game::ROUND4_BEGIN]['data']['negotiation'][FH_Game::NEGOTIATION_3]))
							$quotations = $all_states[FH_Game::ROUND4_BEGIN]['data']['negotiation'][FH_Game::NEGOTIATION_3];
						else
							$quotations = isset ($all_states[FH_Game::ROUND2_BEGIN]['data']['quotations']) ? $all_states[FH_Game::ROUND2_BEGIN]['data']['quotations'] : [];
						}

					foreach ($computed as $quality_slug => $value) {
						if (!empty ($quotations))
							foreach ($quotations as $quotation_id => $quotation_data)
								if ($quotation_data['product'] == $key-> get() && $quotation_data['quality'] == $quality_slug) {
									if (isset ($quotation_data['payment_term'])) {
										$computed[$quality_slug] += $scenario->get ('financing_cost') * $quotation_data['payment_term'] * $quotation_data['price'] * 0.01;
										}
									}
						}

					foreach ($computed as $quality_slug => $value)
						$computed[$quality_slug] = sprintf ('%.2f', $value);
					break;
				case 'rtwo_desired_financing_cost':
				case 'desired_financing_cost':
					foreach ($computed as $quality_slug => $value)
						$computed[$quality_slug] = $qualities[$quality_slug]['desired_price'] * $qualities[$quality_slug]['desired_payment_term'] * $scenario->get ('financing_cost') * 0.01;

					foreach ($computed as $quality_slug => $value)
						$computed[$quality_slug] = sprintf ('%.2f', $value);
					break;
				case 'rtwo_average_warranty_cost':
				case 'average_warranty_cost':
					/***#***/
					if ($opts == 'rtwo_average_warranty_cost')
						$quotations = isset ($all_states[FH_Game::ROUND2_BEGIN]['data']['quotations']) ? $all_states[FH_Game::ROUND2_BEGIN]['data']['quotations'] : [];
					else {
						if (isset ($all_states[FH_Game::ROUND4_BEGIN]['submitted']) && isset ($all_states[FH_Game::ROUND4_BEGIN]['data']['negotiation'][FH_Game::NEGOTIATION_3]))
							$quotations = $all_states[FH_Game::ROUND4_BEGIN]['data']['negotiation'][FH_Game::NEGOTIATION_3];
						else
							$quotations = isset ($all_states[FH_Game::ROUND2_BEGIN]['data']['quotations']) ? $all_states[FH_Game::ROUND2_BEGIN]['data']['quotations'] : [];
						}

					$average_unit_cost = $this->compute ($key, 'average_unit_cost');

					foreach ($computed as $quality_slug => $value) {
						if (!empty ($quotations))
							foreach ($quotations as $quotation_id => $quotation_data)
								if ($quotation_data['product'] == $key-> get() && $quotation_data['quality'] == $quality_slug) {
									if (isset ($quotation_data['warranty']) && $quotation_data['warranty'] != 'none') {
										$warranty = new FH_Warranty ($key->get ('path'), $quotation_data['warranty']);
										$computed[$quality_slug] += $warranty->get ('cost') *
											// $qualities[$quality_slug]['unit_cost'] * 0.01;
											$average_unit_cost[$quality_slug] * 0.01;
										}
									}
						}

					foreach ($computed as $quality_slug => $value)
						$computed[$quality_slug] = sprintf ('%.2f', $value);
					break;
				case 'rtwo_desired_warranty_cost':
					$warranties = new FH_List ('FH_Warranty', $game->scenario ('path'));
					foreach ($computed as $quality_slug => $value) {
						if (!$warranties->is ('empty'))
							foreach ($warranties->get () as $warranty) {
								if ($warranty->get ('mandatory'))
									$computed[$quality_slug] = 0.01 * $warranty->get ('cost') * $qualities[$quality_slug]['desired_price'];
								}
						}

					foreach ($computed as $quality_slug => $value)
						$computed[$quality_slug] = sprintf ('%.2f', $value);
					break;
				case 'desired_warranty_cost':
					$desired_warranty = $this->compute ('counter_warranty');
					$warranties = new FH_List ('FH_Warranty', $game->scenario ('path'));
					foreach ($computed as $quality_slug => $value) {
						if (!$warranties->is ('empty'))
							foreach ($warranties->get () as $warranty) {
								if ($warranty->get () == $desired_warranty[$key->get ()][$quality_slug])
									$computed[$quality_slug] = 0.01 * $warranty->get ('cost') * $qualities[$quality_slug]['desired_price'];
								}
						}

					foreach ($computed as $quality_slug => $value)
						$computed[$quality_slug] = sprintf ('%.2f', $value);
					break;
				case 'rtwo_quoted_unit_price':
				case 'quoted_unit_price':
					/***#***/
					if ($opts == 'rtwo_quoted_unit_price')
						$quotations = isset ($all_states[FH_Game::ROUND2_BEGIN]['data']['quotations']) ? $all_states[FH_Game::ROUND2_BEGIN]['data']['quotations'] : [];
					else {
						if (isset ($all_states[FH_Game::ROUND4_BEGIN]['submitted']) && isset ($all_states[FH_Game::ROUND4_BEGIN]['data']['negotiation'][FH_Game::NEGOTIATION_3]))
							$quotations = $all_states[FH_Game::ROUND4_BEGIN]['data']['negotiation'][FH_Game::NEGOTIATION_3];
						else
							$quotations = isset ($all_states[FH_Game::ROUND2_BEGIN]['data']['quotations']) ? $all_states[FH_Game::ROUND2_BEGIN]['data']['quotations'] : [];
						}

					$quoted = [];
					$income = [];
					foreach ($computed as $quality_slug => $value) {
						$quoted[$quality_slug] = 0;
						$income[$quality_slug] = 0;
						if (!empty ($quotations))
							foreach ($quotations as $quotation_id => $quotation_data)
								if ($quotation_data['product'] == $key-> get() && $quotation_data['quality'] == $quality_slug) {
									if (isset ($quotation_data['price']) && $quotation_data['price'] > 0) {
										$quoted[$quality_slug] += $quotation_data['quantity'];
										$income[$quality_slug] += $quotation_data['quantity'] * $quotation_data['price'];
										}
									}
						}

					foreach ($computed as $quality_slug => $value)
						$computed[$quality_slug] = sprintf ('%.2f', $quoted[$quality_slug] > 0 ? $income[$quality_slug] / $quoted[$quality_slug] : 'N/A');
					break;
				case 'desired_unit_price':
					foreach ($qualities as $quality_slug => $quality_data)
						$computed[$quality_slug] = sprintf ('%.2f', $quality_data['desired_price']);
					break;
				case 'average_unit_margin':
					$average_unit_cost	= $this->compute ($key, 'average_unit_cost');
					$average_features_cost	= $this->compute ($key, 'average_features_cost');
					$average_delivery_cost	= $this->compute ($key, 'average_delivery_cost');
					$average_promotion_cost	= $this->compute ($key, 'average_promotion_cost');
					$average_financing_cost	= $this->compute ($key, 'average_financing_cost');
					$average_warranty_cost	= $this->compute ($key, 'average_warranty_cost');
					$quoted_unit_price	= $this->compute ($key, 'quoted_unit_price');

					foreach ($computed as $quality_slug => $value) {
						if (
							$quoted_unit_price[$quality_slug] == 'N/A' ||
							$average_unit_cost[$quality_slug] == 'N/A' ||
							$average_features_cost[$quality_slug] == 'N/A' ||
							$average_delivery_cost[$quality_slug] == 'N/A' ||
							$average_promotion_cost[$quality_slug] == 'N/A' ||
							$average_financing_cost[$quality_slug] == 'N/A' ||
							$average_warranty_cost[$quality_slug] == 'N/A'
							) {
							$computed[$quality_slug] = 'N/A';
							continue;
							}
						$computed[$quality_slug] = sprintf ('%.2f',
							$quoted_unit_price[$quality_slug] -
							$average_unit_cost[$quality_slug] -
							$average_features_cost[$quality_slug] -
							$average_delivery_cost[$quality_slug] -
							$average_promotion_cost[$quality_slug] -
							$average_financing_cost[$quality_slug] -
							$average_warranty_cost[$quality_slug]);
						}
					break;
				case 'average_margin':
					$average_unit_margin	= $this->compute ($key, 'average_unit_margin');
					$quoted_unit_price	= $this->compute ($key, 'quoted_unit_price');

					foreach ($computed as $quality_slug => $value) {
						if (
							$average_unit_margin[$quality_slug] == 'N/A' ||
							$quoted_unit_price[$quality_slug] == 'N/A'
							) {
							$computed[$quality_slug] = 'N/A';
							continue;
							}
						if (isset ($quoted_unit_price[$quality_slug]) && $quoted_unit_price[$quality_slug] > 0)
							$computed[$quality_slug] = sprintf ('%.2f', 100 * $average_unit_margin[$quality_slug] / $quoted_unit_price[$quality_slug]);
						else
							$computed[$quality_slug] = 'N/A';
						}
					break;
				case 'total_margin':
					$units_quoted		= $this->compute ($key, 'units_quoted');
					$average_unit_margin	= $this->compute ($key, 'average_unit_margin');

					foreach ($computed as $quality_slug => $value) {
						if (
							$average_unit_margin[$quality_slug] == 'N/A'
							) {
							$computed[$quality_slug] = 'N/A';
							continue;
							}
						$computed[$quality_slug] = sprintf ('%.0f', $units_quoted[$quality_slug] * $average_unit_margin[$quality_slug]);
						}
					break;
				case 'rtwo_perceived_cost':
					$computed = [];

						$quoted_unit_price	= $this->compute ($key, 'rtwo_quoted_unit_price');
						$average_features_cost	= $this->compute ($key, 'rtwo_average_features_cost');
						$average_delivery_cost	= $this->compute ($key, 'rtwo_average_delivery_cost');
						$average_promotion_cost	= $this->compute ($key, 'rtwo_average_promotion_cost');
						$average_financing_cost	= $this->compute ($key, 'rtwo_average_financing_cost');
						$average_warranty_cost	= $this->compute ($key, 'rtwo_average_warranty_cost');

/*echo $something;
echo $this->get ('name');
						echo "
quoted_unit_price = " . var_export ($quoted_unit_price, TRUE) . "
average_features_cost = " . var_export ($average_features_cost, TRUE) . "
average_delivery_cost = " . var_export ($average_delivery_cost, TRUE) . "
average_promotion_cost = " . var_export ($average_promotion_cost, TRUE) . "
average_financing_cost = " . var_export ($average_financing_cost, TRUE) . ",
average_warranty_cost = " . var_export ($average_warranty_cost, TRUE) . "
";*/

						foreach ($quoted_unit_price as $quality_slug => $value) {
							$computed[$quality_slug] = sprintf ('%.2f', 
								$quoted_unit_price[$quality_slug]	* $game->scenario ('price_weight') -
								$average_features_cost[$quality_slug]	* $game->scenario ('features_weight') -
								$average_delivery_cost[$quality_slug]	* $game->scenario ('delivery_weight') -
								$average_promotion_cost[$quality_slug]	* $game->scenario ('adv_budg_weight') -
								$average_financing_cost[$quality_slug]	* $game->scenario ('paym_term_weight') -
								$average_warranty_cost[$quality_slug]	* $game->scenario ('warranty_weight'));
							}
// echo "perceived_cost = " . var_export ($computed, TRUE) . "\n\n";
					break;

				case 'rtwo_desired_value':
					$computed = [];

						$quoted_unit_price	= $this->compute ($key, 'desired_unit_price');
						$average_features_cost	= $this->compute ($key, 'rtwo_desired_features_cost');
						$average_delivery_cost	= $this->compute ($key, 'rtwo_desired_delivery_cost');
						$average_promotion_cost	= $this->compute ($key, 'rtwo_desired_promotion_cost');
						$average_financing_cost	= $this->compute ($key, 'desired_financing_cost');
						$average_warranty_cost	= $this->compute ($key, 'rtwo_desired_warranty_cost');

						foreach ($quoted_unit_price as $quality_slug => $value) {
							$computed[$quality_slug] = sprintf ('%.2f', 
								$quoted_unit_price[$quality_slug]	* $game->scenario ('price_weight') -
								$average_features_cost[$quality_slug]	* $game->scenario ('features_weight') -
								$average_delivery_cost[$quality_slug]	* $game->scenario ('delivery_weight') -
								$average_promotion_cost[$quality_slug]	* $game->scenario ('adv_budg_weight') -
								$average_financing_cost[$quality_slug]	* $game->scenario ('paym_term_weight') -
								$average_warranty_cost[$quality_slug]	* $game->scenario ('warranty_weight'));
							}

/*echo $this->get ('name');
echo "game delivery weight " . $game->scenario ('delivery_weight') . "\n";
						echo "
quoted_unit_price = " . var_export ($quoted_unit_price, TRUE) . "
average_features_cost = " . var_export ($average_features_cost, TRUE) . "
average_delivery_cost = " . var_export ($average_delivery_cost, TRUE) . "
average_promotion_cost = " . var_export ($average_promotion_cost, TRUE) . "
average_financing_cost = " . var_export ($average_financing_cost, TRUE) . ",
average_warranty_cost = " . var_export ($average_warranty_cost, TRUE) . "
";
echo "desired_cost = " . var_export ($computed, TRUE) . "\n\n";*/
					break;

				case 'desired_value':
					$computed = [];

						$quoted_unit_price	= $this->compute ($key, 'desired_unit_price');
						$average_features_cost	= $this->compute ($key, 'desired_features_cost');
						$average_delivery_cost	= $this->compute ($key, 'desired_delivery_cost');
						$average_promotion_cost	= $this->compute ($key, 'desired_promotion_cost');
						$average_financing_cost	= $this->compute ($key, 'desired_financing_cost');
						$average_warranty_cost	= $this->compute ($key, 'desired_warranty_cost');

						foreach ($quoted_unit_price as $quality_slug => $value) {
							$computed[$quality_slug] = sprintf ('%.2f', 
								$quoted_unit_price[$quality_slug]	* $game->scenario ('price_weight') -
								$average_features_cost[$quality_slug]	* $game->scenario ('features_weight') -
								$average_delivery_cost[$quality_slug]	* $game->scenario ('delivery_weight') -
								$average_promotion_cost[$quality_slug]	* $game->scenario ('adv_budg_weight') -
								$average_financing_cost[$quality_slug]	* $game->scenario ('paym_term_weight') -
								$average_warranty_cost[$quality_slug]	* $game->scenario ('warranty_weight'));
							}

/*echo $this->get ('name');
						echo "
quoted_unit_price = " . var_export ($quoted_unit_price, TRUE) . "
average_features_cost = " . var_export ($average_features_cost, TRUE) . "
average_delivery_cost = " . var_export ($average_delivery_cost, TRUE) . "
average_promotion_cost = " . var_export ($average_promotion_cost, TRUE) . "
average_financing_cost = " . var_export ($average_financing_cost, TRUE) . ",
average_warranty_cost = " . var_export ($average_warranty_cost, TRUE) . "
";
echo "desired_cost = " . var_export ($computed, TRUE) . "\n\n";*/
					break;

/*
		'quantity'		=> FH_Theme::r ('quantity'),
		'features'		=> FH_Theme::r ('features'),
		'location'		=> FH_Theme::r ('location'),
		'delivery_term'		=> FH_Theme::r ('delivery_term'),
		'price'			=> FH_Theme::r ('price'),
		'advertising_budget'	=> FH_Theme::r ('advertising_budget'),
		'payment_term'		=> FH_Theme::r ('payment_term'),
		'warranty'		=> FH_Theme::r ('warranty')
*/
				}
			return $this->computed[$key->get ()][$opts] = $computed;
			}
		if (is_string ($key)) {
			if (isset ($this->computed[$key]))
				return $this->computed[$key];

			$game = new FH_Game ((int) $this->data['game']);
			$products = new FH_List ('FH_Product', $game->scenario ('path'));

			switch ($key) {
				case 'income':
					$computed = 0;

					foreach ($products->get () as $product) {
						$units_quoted		= $this->compute ($product, 'units_quoted');
						$quoted_unit_price	= $this->compute ($product, 'quoted_unit_price');

						foreach ($units_quoted as $quality_slug => $value) {
							if ($value == 'N/A' || $quoted_unit_price == 'N/A') continue;
							$computed += $value * $quoted_unit_price[$quality_slug];
							}
						}

					$computed = sprintf ('%.0f', $computed);
					break;
				case 'total_cost':
					$computed = 0;

					foreach ($products->get () as $product) {
						$units_quoted		= $this->compute ($product, 'units_quoted');
						$average_unit_cost	= $this->compute ($product, 'average_unit_cost');
						$average_features_cost	= $this->compute ($product, 'average_features_cost');
						$average_delivery_cost	= $this->compute ($product, 'average_delivery_cost');
						$average_promotion_cost	= $this->compute ($product, 'average_promotion_cost');
						$average_financing_cost	= $this->compute ($product, 'average_financing_cost');
						$average_warranty_cost	= $this->compute ($product, 'average_warranty_cost');

						foreach ($units_quoted as $quality_slug => $value) {
							if (
								$value == 'N/A' ||
								$average_unit_cost[$quality_slug] == 'N/A' ||
								$average_features_cost[$quality_slug] == 'N/A' ||
								$average_delivery_cost[$quality_slug] == 'N/A' ||
								$average_promotion_cost[$quality_slug] == 'N/A' ||
								$average_financing_cost[$quality_slug] == 'N/A' ||
								$average_warranty_cost[$quality_slug] == 'N/A'
								) {
								continue;
								}
							$computed += $units_quoted[$quality_slug] *
								($average_unit_cost[$quality_slug] +
								$average_features_cost[$quality_slug] +
								$average_delivery_cost[$quality_slug] +
								$average_promotion_cost[$quality_slug] +
								$average_financing_cost[$quality_slug] +
								$average_warranty_cost[$quality_slug]);
							}
						}

					$computed = sprintf ('%.0f', $computed);
					break;
				case 'global_margin_rel':
					$income		= $this->compute ('income');
					$total_cost	= $this->compute ('total_cost');
					$computed = sprintf ('%.2f', $income != 0 ? 100 * ($income - $total_cost) / $income : 'N/A');
					break;
				case 'global_margin_abs':
					$income		= $this->compute ('income');
					$total_cost	= $this->compute ('total_cost');
					$computed = sprintf ('%.0f', $income - $total_cost);
					break;

				case 'perceived_cost':
					$computed = [];

					foreach ($products->get () as $product) {
						$quoted_unit_price	= $this->compute ($product, 'quoted_unit_price');
						$average_features_cost	= $this->compute ($product, 'average_features_cost');
						$average_delivery_cost	= $this->compute ($product, 'average_delivery_cost');
						$average_promotion_cost	= $this->compute ($product, 'average_promotion_cost');
						$average_financing_cost	= $this->compute ($product, 'average_financing_cost');
						$average_warranty_cost	= $this->compute ($product, 'average_warranty_cost');

						foreach ($quoted_unit_price as $quality_slug => $value) {
							$computed[$product->get ()][$quality_slug] = sprintf ('%.2f', 
								$quoted_unit_price[$quality_slug]	* $game->scenario ('price_weight') -
								$average_features_cost[$quality_slug]	* $game->scenario ('features_weight') -
								$average_delivery_cost[$quality_slug]	* $game->scenario ('delivery_weight') -
								$average_promotion_cost[$quality_slug]	* $game->scenario ('adv_budg_weight') -
								$average_financing_cost[$quality_slug]	* $game->scenario ('paym_term_weight') -
								$average_warranty_cost[$quality_slug]	* $game->scenario ('warranty_weight'));
							}
						}
					break;

				case 'rtwo_perceived_cost':
					$computed = [];

					foreach ($products->get () as $product) {
						$quoted_unit_price	= $this->compute ($product, 'rtwo_quoted_unit_price');
						$average_features_cost	= $this->compute ($product, 'rtwo_average_features_cost');
						$average_delivery_cost	= $this->compute ($product, 'rtwo_average_delivery_cost');
						$average_promotion_cost	= $this->compute ($product, 'rtwo_average_promotion_cost');
						$average_financing_cost	= $this->compute ($product, 'rtwo_average_financing_cost');
						$average_warranty_cost	= $this->compute ($product, 'rtwo_average_warranty_cost');

						foreach ($quoted_unit_price as $quality_slug => $value) {
							$computed[$product->get ()][$quality_slug] = sprintf ('%.2f', 
								$quoted_unit_price[$quality_slug]	* $game->scenario ('price_weight') -
								$average_features_cost[$quality_slug]	* $game->scenario ('features_weight') -
								$average_delivery_cost[$quality_slug]	* $game->scenario ('delivery_weight') -
								$average_promotion_cost[$quality_slug]	* $game->scenario ('adv_budg_weight') -
								$average_financing_cost[$quality_slug]	* $game->scenario ('paym_term_weight') -
								$average_warranty_cost[$quality_slug]	* $game->scenario ('warranty_weight'));
							}
						}

					break;

				case 'desired_value':
					$computed = [];

					foreach ($products->get () as $product) {
						$quoted_unit_price	= $this->compute ($product, 'desired_unit_price');
						$average_features_cost	= $this->compute ($product, 'desired_features_cost');
						$average_delivery_cost	= $this->compute ($product, 'desired_delivery_cost');
						$average_promotion_cost	= $this->compute ($product, 'desired_promotion_cost');
						$average_financing_cost	= $this->compute ($product, 'desired_financing_cost');
						$average_warranty_cost	= $this->compute ($product, 'desired_warranty_cost');

						foreach ($quoted_unit_price as $quality_slug => $value) {
							$computed[$product->get ()][$quality_slug] = sprintf ('%.2f', 
								$quoted_unit_price[$quality_slug]	* $game->scenario ('price_weight') +
								$average_features_cost[$quality_slug]	* $game->scenario ('features_weight') +
								$average_delivery_cost[$quality_slug]	* $game->scenario ('delivery_weight') +
								$average_promotion_cost[$quality_slug]	* $game->scenario ('adv_budg_weight') +
								$average_financing_cost[$quality_slug]	* $game->scenario ('paym_term_weight') +
								$average_warranty_cost[$quality_slug]	* $game->scenario ('warranty_weight'));
							}
						}
					break;

				case 'perceived_quantities':
					$computed = [];

					$perceived_cost = $this->compute ('perceived_cost');
					
					foreach ($products->get () as $product) {
						$units_quoted		= $this->compute ($product, 'units_quoted');
						$qualities		= $product->get ('quality');
						foreach ($units_quoted as $quality_slug => $value) {
							$computed[$product->get ()][$quality_slug] = [
								$units_quoted[$quality_slug],
								$qualities[$quality_slug]['desired_quantity'],
								$perceived_cost[$product->get ()][$quality_slug] * $qualities[$quality_slug]['desired_quantity']
								];
							}
						} 
					break;

				case 'counter_features':
					$cqs = $this->get ('rtwo_processed_quotations');
					$players = new FH_List ('FH_Player', [sprintf ('game=%d', $game->get ())]);
					$pqs = [];
					foreach ($players->get () as $player)
						$pqs[$player->get ()] = $player->get ('rtwo_processed_quotations');

					$features = new FH_List ('FH_Feature', $game->scenario ('path'));

					$negotiation_step = isset ($all_states[FH_Game::ROUND4_BEGIN]['step']) ? $all_states[FH_Game::ROUND4_BEGIN]['step'] : FH_Game::NEGOTIATION_0;
					
					$computed = [];
					if (!empty ($cqs))
						foreach ($cqs as $product_slug => $product_quotation) {
							if (!empty ($product_quotation))
								foreach ($product_quotation as $quality_slug => $quality_quotation) {
									$computed[$product_slug][$quality_slug] = [];

									if (!$features->is ('empty'))
										foreach ($features->get () as $feature) {
											$offered = in_array ($feature->get (), $quality_quotation['features']);
											$step = $this->get ('negotiation_step');

											$option = ($feature->get ('mandatory') ? 10 : 0) + ($feature->get ('negotiable') ? 1 : 0);
											switch ($option) {
												case 0:
													if (in_array ($feature->get (), $quality_quotation['features']))
														$computed[$product_slug][$quality_slug][] = $feature->get ();
													break;
												case 1:
													$count = [ 'all' => 0, 'offered' => 0 ];
													foreach ($pqs as $player_id => $pq) {
														#if ($player_id == $this->ID) continue;
														if ($pq[$product_slug][$quality_slug]['quantity'] == 0) continue;

														if (in_array ($feature->get (), $pq[$product_slug][$quality_slug]['features']))
															$count['offered'] ++;
														$count['all'] ++;
														}

													switch ($game->scenario ('ask_for_features')) {
														case 'never':
															break;
														case 'if_any':
															if ($count['offered'] > 0 && in_array ($step, [FH_Game::NEGOTIATION_0, FH_Game::NEGOTIATION_1]))
																$computed[$product_slug][$quality_slug][] = $feature->get ();
															break;
														case 'if_most':
															if ($count['offered'] > $count['all'] / 2 && in_array ($step, [FH_Game::NEGOTIATION_0, FH_Game::NEGOTIATION_1, FH_Game::NEGOTIATION_2]))
																$computed[$product_slug][$quality_slug][] = $feature->get ();
															break;
														case 'if_all':
															if ($count['offered'] == $count['all'])
																$computed[$product_slug][$quality_slug][] = $feature->get ();
															break;
														case 'always':
															$computed[$product_slug][$quality_slug][] = $feature->get ();
															break;
														}
													break;
												case 10:
												case 11:
													$computed[$product_slug][$quality_slug][] = $feature->get ();
													break;
												}
											if ($offered && !in_array ($feature->get (), $computed[$product_slug][$quality_slug]))
												$computed[$product_slug][$quality_slug][] = $feature->get ();
											}
									}
							}
					break;
				case 'counter_warranty':
					$cqs = $this->get ('rtwo_processed_quotations');

					$players = new FH_List ('FH_Player', [sprintf ('game=%d', $game->get ())]);
					$pqs = [];
					foreach ($players->get () as $player)
						$pqs[$player->get ()] = $player->get ('rtwo_processed_quotations');

					$warranties = new FH_List ('FH_Warranty', $game->scenario ('path'));
					
					$computed = [];
					if (!empty ($cqs))
						foreach ($cqs as $product_slug => $product_quotation) {
							if (!empty ($product_quotation))
								foreach ($product_quotation as $quality_slug => $quality_quotation) {
									$computed[$product_slug][$quality_slug] = [];

									if (!$warranties->is ('empty'))
										foreach ($warranties->get () as $warranty) {
											$better = $warranty->get ('better');
											$intersect = array_intersect ($better, $quality_quotation['warranties']);
											$offered = !empty ($intersect);
											$step = $this->get ('negotiation_step');

											$option = ($warranty->get ('mandatory') ? 10 : 0) + ($warranty->get ('negotiable') ? 1 : 0);
											switch ($option) {
												case 0:
													if (in_array ($warranty->get (), $quality_quotation['warranties']))
														$computed[$product_slug][$quality_slug] = $warranty->get ();
													break;
												case 1:
													$count = [ 'all' => 0, 'offered' => 0 ];
													foreach ($pqs as $player_id => $pq) {
														#if ($player_id == $this->ID) continue;
														if ($pq[$product_slug][$quality_slug]['quantity'] == 0) continue;

														$pi = array_intersect ($better, $pq[$product_slug][$quality_slug]['warranties']);

														if (!empty ($pi))
															$count['offered'] ++;
														$count['all'] ++;
														}

													switch ($game->scenario ('ask_for_warranty')) {
														case 'never':
															break;
														case 'if_any':
															if ($count['offered'] > 0 && in_array ($step, [FH_Game::NEGOTIATION_0, FH_Game::NEGOTIATION_1]))
																$computed[$product_slug][$quality_slug][] = $warranty->get ();
															break;
														case 'if_most':
															if ($count['offered'] >= $count['all'] / 2 && in_array ($step, [FH_Game::NEGOTIATION_0, FH_Game::NEGOTIATION_1, FH_Game::NEGOTIATION_2]))

																$computed[$product_slug][$quality_slug][] = $warranty->get ();
															break;
														case 'if_all':
															if ($count['offered'] == $count['all'])
																$computed[$product_slug][$quality_slug][] = $warranty->get ();
															break;
														case 'always':
															$computed[$product_slug][$quality_slug][] = $warranty->get ();
															break;
														}
													break;
												case 10:
												case 11:
													$computed[$product_slug][$quality_slug][] = $warranty->get ();
													break;
												}

											if ($offered && !in_array ($warranty->get (), $computed[$product_slug][$quality_slug]))
												$computed[$product_slug][$quality_slug][] = $warranty->get ();
											}
									}
							}
					if (!empty ($computed))
						foreach ($computed as $product_slug => $product_computed) {
							if (!empty ($product_computed))
								foreach ($product_computed as $quality_slug => $quality_computed) {
									if (!empty ($quality_computed)) {
										$max = ['none', 0];
										foreach ($quality_computed as $warranty_slug) {
											$warranty = new FH_Warranty ($game->scenario ('path'), $warranty_slug);
											if ($max[1] < $warranty->get ('cost'))
												$max = [ $warranty->get (), $warranty->get ('cost') ];
											}
										$computed[$product_slug][$quality_slug] = $max[0];
										}
									else
										$computed[$product_slug][$quality_slug] = 'none';
									}
							}

					$pq = $this->get ('processed_quotations');
					if (!empty ($pq))
						foreach ($pq as $product_slug => $product_quoted) {
							if (!empty ($product_quoted))
								foreach ($product_quoted as $quality_slug => $quality_quoted) {
									if (!isset ($computed[$product_slug][$quality_slug]))
										$computed[$product_slug][$quality_slug] = 'none';

									$max = ['none', 0];
									if (!empty ($quality_quoted['warranties']))
										foreach ($quality_quoted['warranties'] as $warranty_slug) {
											try {
												$warranty = new FH_Warranty ($game->scenario ('path'), $warranty_slug);
												if ($max[1] < $warranty->get ('cost'))
													$max = [ $warranty->get (), $warranty->get ('cost') ];
												}
											catch (FH_Exception $e) {
												}
											}
	
									try {
										$warranty = new FH_Warranty ($game->scenario ('path'), $computed[$product_slug][$quality_slug]);
										if ($max[1] < $warranty->get ('cost'))
											$max = [ $warranty->get (), $warranty->get ('cost') ];
										}
									catch (FH_Exception $e) {
										}

									$computed[$product_slug][$quality_slug] = $max[0];
									}
							}
					break;
				case 'counter_price':
					$score = $this->get ('score');

					$cqs = $this->get ('processed_quotations');
					$players = new FH_List ('FH_Player', [sprintf ('game=%d', $game->get ())]);
					$pqs = [];
					foreach ($players->get () as $player)
						$pqs[$player->get ()] = $player->get ('processed_quotations');
					$counter = $this->get ('counter');
					$best = $this->get ('best');

					$computed = [];
					if (!empty ($cqs))
						foreach ($cqs as $product_slug => $product_quotation) {
							if (!empty ($product_quotation))
								foreach ($product_quotation as $quality_slug => $quality_quotation) {
									$computed[$product_slug][$quality_slug] = 0;

									$quoted = [ (float) $quality_quotation['parameters']['max_price_accepted'] ];
									if (self::SHARE_INFO) {
										if (!empty ($pqs))
											foreach ($pqs as $player_id => $pq)
												$quoted[] = (float) $pq[$product_slug][$quality_slug]['price'];
										}
									else
										$quoted[] = (float) $quality_quotation['price'];
									
									$quoted = min ($quoted);
									$desired = empty ($counter) ? $quality_quotation['parameters']['desired_price'] : $counter['price'][$product_slug][$quality_slug];
									$quoted = max ([ $quoted, $desired ]);

									$factor = (9 - $quality_quotation['parameters']['aggressiveness']) * (900 + $quality_quotation['parameters']['aggressiveness'] * $quality_quotation['parameters']['score_weight'] * ($score - 100)) / 8100;

									$computed[$product_slug][$quality_slug] = sprintf ('%.2f', min ([ 
										!isset ($best['price']) ? $quality_quotation['price'] : $best['price'][$product_slug][$quality_slug],
										$desired + ($quoted - $desired) * $factor
										])); /**X**/
									}
							}
					break;
				case 'counter_delivery_term':
					$score = $this->get ('score');

					$cqs = $this->get ('processed_quotations');
					$players = new FH_List ('FH_Player', [sprintf ('game=%d', $game->get ())]);
					$pqs = [];
					foreach ($players->get () as $player)
						$pqs[$player->get ()] = $player->get ('processed_quotations');
					$counter = $this->get ('counter');
					$best = $this->get ('best');

					$computed = [];
					if (!empty ($cqs))
						foreach ($cqs as $product_slug => $product_quotation) {
							if (!empty ($product_quotation))
								foreach ($product_quotation as $quality_slug => $quality_quotation) {
									$computed[$product_slug][$quality_slug] = 0;

									$quoted = [ $quality_quotation['parameters']['max_delivery_time'] ];
									if (self::SHARE_INFO) {
										if (!empty ($pqs))
											foreach ($pqs as $player_id => $pg)
												$quoted[] = $pg[$product_slug][$quality_slug]['delivery_term'];
										}
									else
										$quoted[] = $quality_quotation['delivery_term'];
									
									$quoted = min ($quoted);
									$desired = empty ($counter) ? $quality_quotation['parameters']['desired_delivery_time'] : $counter['delivery_term'][$product_slug][$quality_slug];
									$quoted = max ([ $quoted, $desired ]);

									$factor = (9 - $quality_quotation['parameters']['aggressiveness'] * (1 - $quality_quotation['parameters']['sweetener'])) * (900 + $quality_quotation['parameters']['aggressiveness'] * (1 - $quality_quotation['parameters']['sweetener']) * $quality_quotation['parameters']['score_weight'] * ($score - 100)) / 8100;

									$computed[$product_slug][$quality_slug] = floor (min ([ !isset ($best['delivery_term']) ? $quality_quotation['delivery_term'] : $best['delivery_term'][$product_slug][$quality_slug], $desired + ($quoted - $desired) * $factor ]));
									}
							}
					break;
				case 'counter_payment_term':
					$score = $this->get ('score');

					$cqs = $this->get ('processed_quotations');
					$players = new FH_List ('FH_Player', [sprintf ('game=%d', $game->get ())]);
					$pqs = [];
					foreach ($players->get () as $player)
						$pqs[$player->get ()] = $player->get ('processed_quotations');
					$counter = $this->get ('counter');
					$best = $this->get ('best');

					$computed = [];
					if (!empty ($cqs))
						foreach ($cqs as $product_slug => $product_quotation) {
							if (!empty ($product_quotation))
								foreach ($product_quotation as $quality_slug => $quality_quotation) {
									$computed[$product_slug][$quality_slug] = 0;

									$quoted = [ $quality_quotation['parameters']['min_payment_term_accepted'] ];
									if (self::SHARE_INFO) {
										if (!empty ($pqs))
											foreach ($pqs as $player_id => $pg)
												$quoted[] = $pg[$product_slug][$quality_slug]['payment_term'];
										}
									else
										$quoted[] = $quality_quotation['payment_term'];
									
									$quoted = max ($quoted);
									$desired = empty ($counter) ? $quality_quotation['parameters']['desired_payment_term'] : $counter['payment_term'][$product_slug][$quality_slug];
									$quoted = min ([ $quoted, $desired ]);

									$factor = (9 - $quality_quotation['parameters']['aggressiveness']) * (900 + $quality_quotation['parameters']['aggressiveness'] * $quality_quotation['parameters']['score_weight'] * ($score - 100)) / 8100;

									$computed[$product_slug][$quality_slug] = floor (max ([ !isset ($best['payment_term']) ? $quality_quotation['payment_term'] : $best['payment_term'][$product_slug][$quality_slug], $desired - ($desired - $quoted) * $factor ]));
									}
							}
					break;
				case 'counter_advertising_budget':
					$score = $this->get ('score');

					$cqs = $this->get ('processed_quotations');
					$players = new FH_List ('FH_Player', [sprintf ('game=%d', $game->get ())]);
					$pqs = [];
					foreach ($players->get () as $player)
						$pqs[$player->get ()] = $player->get ('processed_quotations');
					$counter = $this->get ('counter');
					$best = $this->get ('best');

					$computed = [];
					if (!empty ($cqs))
						foreach ($cqs as $product_slug => $product_quotation) {
							if (!empty ($product_quotation))
								foreach ($product_quotation as $quality_slug => $quality_quotation) {
									$computed[$product_slug][$quality_slug] = 0;

									$quoted = [ $quality_quotation['parameters']['min_adv_budget_accepted'] ];
									if (self::SHARE_INFO) {
										if (!empty ($pqs))
											foreach ($pqs as $player_id => $pg)
												$quoted[] = $pg[$product_slug][$quality_slug]['advertising_budget'];
										}
									else
										$quoted[] = $quality_quotation['advertising_budget'];
									
									$quoted = max ($quoted);
									$desired = empty ($counter) ? $quality_quotation['parameters']['desired_adv_budget'] : $counter['advertising_budget'][$product_slug][$quality_slug];
									$quoted = min ([ $quoted, $desired ]);

									$factor = (9 - $quality_quotation['parameters']['aggressiveness']) * (900 + $quality_quotation['parameters']['aggressiveness'] * $quality_quotation['parameters']['score_weight'] * ($score - 100)) / 8100;

									$computed[$product_slug][$quality_slug] = floor (max ([ !isset ($best['advertising_budget']) ? $quality_quotation['advertising_budget'] : $best['advertising_budget'][$product_slug][$quality_slug], $desired - ($desired - $quoted) * $factor ]));
									}
							}
					break;
				}

			return $this->computed[$key] = $computed;
			}
		}

	public function quotation ($quotation = null, $format = 'array') {
		try {
			$game = new FH_Game ((int) $this->data['game']);
			}
		catch (FH_Exception $e) {
			return FALSE;
			}
		if (is_array ($quotation))
			$data = $quotation;
		else {
			$quotations = $this->get ('quotations');
			if (isset ($quotations[$quotation]))
				$data = $quotations[$quotation];
			else
				return FALSE;
			}

		try {
			$product = new FH_Product ($game->scenario ('path'), $data['product']);
			}
		catch (FH_Exception $e) {
			return FALSE;
			}
		$qualities = $product->get ('quality');
		$quality = isset ($qualities[$data['quality']]) ? $qualities[$data['quality']] : FALSE;
		if ($quality == FALSE)
			return FALSE;

		if (!isset ($data['location']))
			$location = null;
		else {
			try {
				$location = new FH_Location ($game->scenario ('path'), $data['location']);
				}
			catch (FH_Exception $e) {
				$location = null;
				}
			}

		$quotation = [
			'quantity'	=>	isset ($data['quantity'])	? $data['quantity']		: 0,
			'price'		=>	isset ($data['price'])		? $data['price']		: 0,
			'payment_term'	=>	isset ($data['payment_term'])	? $data['payment_term'] 	: 0,
			'delivery_term'	=>	isset ($data['delivery_term'])	? $data['delivery_term']	: 0,
			'location'	=>	isset ($data['location'])	? $data['location'] 		: '',
			'features'	=>	isset ($data['features'])	? $data['features'] 		: [],
			'warranty'	=>	isset ($data['warranty'])	? $data['warranty'] 		: 'none',
			'advertising'	=>	isset ($data['advertising_budget'])	? $data['advertising_budget'] 		: 0,

			'currency'	=>	$game->scenario ('currency'),
			'product_name'	=>	$product->get ('name'),
			'quality_name'	=>	$quality['name'],
			'unit_type'	=>	$quality['unit_type'],
			'location_name'	=>	is_null ($location) ? '' : $location->get ('name')
			];

		if ($format == 'array')
			return $quotation;

		$quotation['advertising'] = $quotation['advertising'] > 0 ? (/*T[*/'Advertising Budget'/*]*/ . ': ' . $quotation['advertising'] . ' ' . $game->scenario ('currency')) : '';
		if (!empty ($quotation['features'])) {
			$features = new FH_List ('FH_Feature', $game->scenario ('path'));
			$found = [];
			if (!$features->is ('empty'))
				foreach ($features->get () as $feature)
					if (in_array ($feature->get (), $quotation['features']))
						$found[] = $feature->get ();
			$quotation['features'] = /*T[*/'Features'/*]*/ . ': ' . implode (', ', $found);
			}
		else
			$quotation['features'] = '';

		if ($quotation['warranty'] != 'none') {
			$warranty = new FH_Warranty ($game->scenario ('path'), $quotation['warranty']);
			$quotation['warranty'] = /*T[*/'Warranty'/*]*/ . ': ' . $warranty->get ('name');
			}
		else
			$quotation['warranty'] = /*T[*/'Warranty: None'/*]*/;

		if ($format == 'counter') {
			$counter_offer = $this->get ('counter_offer');

			$counter = array_merge ($quotation, [
				'price'		=> $counter_offer['price'][$data['product']][$data['quality']],
				'payment_term'	=> $counter_offer['payment_term'][$data['product']][$data['quality']],
				'delivery_term'	=> $counter_offer['delivery_term'][$data['product']][$data['quality']],
				'features'	=> $counter_offer['features'][$data['product']][$data['quality']],
				'warranty'	=> $counter_offer['warranty'][$data['product']][$data['quality']],
				'advertising'	=> $counter_offer['advertising_budget'][$data['product']][$data['quality']]
				]);

			
			$counter['advertising'] = $counter['advertising'] > 0 ? (/*T[*/'Advertising Budget'/*]*/ . ': ' . $counter['advertising'] . ' ' . $game->scenario ('currency')) : '';
			if (!empty ($counter['features'])) {
				$features = new FH_List ('FH_Feature', $game->scenario ('path'));
				$found = [];
				if (!$features->is ('empty'))
					foreach ($features->get () as $feature)
						if (in_array ($feature->get (), $counter['features']))
							$found[] = $feature->get ();
				$counter['features'] = /*T[*/'Features'/*]*/ . ': ' . implode (', ', $found);
				}
			else
				$counter['features'] = '';

			if ($counter['warranty'] != 'none') {
				try {
					$warranty = new FH_Warranty ($game->scenario ('path'), $counter['warranty']);
					$counter['warranty'] = /*T[*/'Warranty'/*]*/ . ': ' . $warranty->get ('name');
					}
				catch (FH_Exception $e) {
					$counter['warranty'] = /*T[*/'Warranty: None'/*]*/;
					}
				}
			else
				$counter['warranty'] = /*T[*/'Warranty: None'/*]*/;

			foreach ($quotation as $key => $value) {
				if ($key == 'features') {
					if ($counter[$key] != $value) {
						list ($c_text, $c_features) = explode (': ', $counter[$key]);
						if (strpos ($value, ': ') !== FALSE)
							list ($q_text, $q_features) = explode (': ', $value);
						else {
							$q_text = $c_text;
							$q_features = '';
							}

						$q_features = explode (', ', $q_features);
						$c_features = explode (', ', $c_features);
						
						foreach ($c_features as $c_key => $c_feature)
							if (empty ($q_features) || !in_array ($c_feature, $q_features))
								$c_features[$c_key] = '<span class="sd-changed">' . $c_feature . '</span>';

						$quotation[$key] = $c_text . ': ' . implode (', ', $c_features);
						}
					else
						$quotation[$key] = $counter[$key];
					}
				else
					$quotation[$key] = $value == $counter[$key] ? $counter[$key] : ('<span class="sd-changed">' . $counter[$key] . '</span>');
				}
			}

		if ($format == 'render') {
			}
		if ($format == 'docx') {
			return FH_Theme::a (/*T[*/'{quantity} {unit_type} {product_name} {quality_name}, delivered to {location_name} within {delivery_term} days.
{features}   {warranty}   {advertising}
Price/{unit_type}: {price} {currency}   Payment term: {payment_term} days.'/*]*/, $quotation, FALSE);
			}

		return FH_Theme::a (/*T[*/'{quantity} {unit_type} {product_name} {quality_name}, delivered to {location_name} within {delivery_term} days.<br />{features} &nbsp; {warranty} &nbsp; {advertising}<br />Price/{unit_type}: {price} {currency} &nbsp; Payment term: {payment_term} days.'/*]*/, $quotation, FALSE);
		}

	public function report ($html = TRUE, $echo = TRUE) {
		if ($html) {
			$all_states = $this->get ('state');
			$game = new FH_Game ((int) $this->data['game']);
			$products = new FH_List ('FH_Product', $game->scenario ('path'));
			$players = new FH_List ('FH_Player', [ sprintf ('game=%d', $this->data['game']) ]);
			$players->sort ('name');
			$score = [
				'round1'	=> $this->get ('score', 'round1'),
				'round2'	=> $this->get ('score', 'round2'),
				'round3'	=> $this->get ('score', 'round3'),
				'total'		=> $this->get ('score')
				];

			$out = '<div class="sd-report">';

			$out .= '<fieldset>' . "\n\t" . '<legend>' . FH_Theme::__ (/*T[*/'Performance Overview'/*]*/) . '</legend>' . "\n";

			$market = $game->get ('market');
			$this->market = $market[$this->ID];
			$this->computed = [];

			$header_p = '';
			$header_q = '';
			foreach ($products->get () as $product) {
				$qualities = $product->get ('quality');
				$header_p .= "\t\t\t" . '<th' . (sizeof ($qualities) > 1 ? ' colspan="' . sizeof ($qualities) . '"' : '') . '>' . $product->get ('name') . '</th>' . "\n";
				foreach ($qualities as $quality_slug => $quality_data) {
					$header_q .= "\t\t\t" . '<th>' . $quality_data['name'] . '</th>' . "\n";
					}
				}

			$out .= '<div class="table-responsive sd-table">' . "\n";
			$out .= '<table class="table table-striped table-hover">' . "\n\t" . '<thead>' . "\n\t\t" . '<tr>' . "\n\t\t\t" . '<th></th>' . "\n" . $header_p . '</tr>' . "\n\t\t" . '<tr>' . "\n\t\t\t" . '<th></th>' . $header_q . '</tr>' . "\n\t" . '</thead>' . "\n";
			$body_c = '';
			$quoted_price = [];
			foreach (FH_Product::$A as $key => $name) {
				if ($key == 'units_quoted') $name = /*T[*/'Units Sold'/*]*/;
				if ($key == 'quoted_unit_price') $name = /*T[*/'Selling Unit Price'/*]*/;

				$body_c .= '<tr>' . "" . '<th>' . $name . '</th>';
				foreach ($products->get () as $product) {
					$qualities = $product->get ('quality');
					$computed = $this->compute ($product, $key);

					if ($key == 'quoted_unit_price')
						$quoted_price[$product->get ()] = $computed;

					foreach ($qualities as $quality_slug => $quality_data) {
						$body_c .= "\t\t\t" . '<td>' . "\n" . $computed[$quality_slug] . '</td>' . "\n";
						}
					}
				$body_c .= "\t\t" . '</tr>' . "\n";
				}
			$out .= "\t" . '<tbody>' . "\n" . $body_c . '</tbody>' . "\n" . '</table>' . "\n";
			$out .= '</div>' . "\n";

			$header_t = '';
			$body_c = '';
			foreach (self::$A as $key => $name) {
				$computed = $this->compute ($key);
				$header_t .= "\t\t\t" . '<th>' . $name . '</th>' . "\n";
				$body_c .= "\t\t\t" . '<td>' . $computed . '</td>' . "\n";
				}

			$out .= '<div class="table-responsive sd-table-split-4">' . "\n";
			$out .= '<table class="table table-striped table-hover">' . "\n\t" . '<thead>' . "\n\t\t" . '<tr>' . "\n" . $header_t . "\t\t" . '</tr>' . "\n\t" . '</thead>' . "\n";
			$out .= "\t" . '<tbody>' . "\n\t\t" . '<tr>' . "\n" . $body_c . "\t\t" . '</tr>' . "\n\t" . '</tbody>' . "\n" . '</table>' . "\n";
			$out .= '</div>' . "\n";
			
			$out .= '</fieldset>' . "\n";

			$charts = [];
			$titles = [];
			$perceived_costs = [];
			foreach ($players->get () as $player){
				$perceived_costs[$player->get()] = $player->compute ('perceived_cost');
				}
			foreach ($products->get () as $product) {
				$qualities = $product->get ('quality');
				foreach ($qualities as $quality_slug => $quality_data) {
					$charts[$product->get ()][$quality_slug] = [[
						FH_Theme::__ (/*T[*/'Market Share'/*]*/),
						FH_Theme::__ (/*T[*/'Units Acquired'/*]*/)
						]];
					$titles[$product->get ()][$quality_slug] = $product->get ('name') . ' ' . $quality_data['name'] . ' (' . $quality_data['unit_type'] . ')';

					foreach ($players->get () as $player) {
						$this_quoted_price = $player->compute ($product, 'quoted_unit_price');
						
						$charts[$product->get ()][$quality_slug][] = [
							$player->get ('name') . "\n" . 
								'Q' . ': ' . ((int) $market[$player->get ()][$product->get ()][$quality_slug]) . $quality_data['unit_type'] . 
								' P' . ': ' . $this_quoted_price[$quality_slug] . $game->scenario ('currency') .
								' PV' . ': ' . $perceived_costs[$player->get ()][$product->get ()][$quality_slug] . $game->scenario ('currency'),
							(int) $market[$player->get ()][$product->get ()][$quality_slug]
							];
						}
					}
				}

			$out .= '<fieldset>' . "\n\t" . '<legend>' . FH_Theme::__ (/*T[*/'Market Share'/*]*/) . '</legend>' . "\n";

			foreach ($charts as $product_slug => $product_chart) {
				foreach ($product_chart as $quality_slug => $quality_chart) {
					$out .= FH_Theme::c ($quality_chart, $titles[$product_slug][$quality_slug], FALSE);
					}
				}
			
			$out .= '</fieldset>' . "\n";

			$out .= '<fieldset>' . "\n\t" . '<legend>' . htmlentities ($this->get ('name')) . ' ' . FH_Theme::__ (/*T[*/'Final Offer'/*]*/) . '</legend>' . "\n";

			$final_quotations = $this->get ('quotations');

#			if (isset ($all_states[FH_Game::ROUND4_BEGIN]['data']['negotiation'][FH_Game::NEGOTIATION_3]))
#				foreach ($all_states[FH_Game::ROUND4_BEGIN]['data']['negotiation'][FH_Game::NEGOTIATION_3] as $quotation_id => $quotation)
				foreach ($final_quotations as $quotation_id => $quotation)
					$out .= '<div class="sd-quotation-item">' . "\n" . $this->quotation ($quotation, 'render') . "\n" . '</div>';

			$out .= '</fieldset>' . "\n";

			$out .= '<fieldset>' . "\n\t" . '<legend>' . htmlentities ($this->get ('name')) . ' ' . FH_Theme::__ (/*T[*/'Score'/*]*/) . '</legend>' . "\n";

			$out .= '<div class="table-responsive sd-table-split-4">
		<table class="table table-striped table-hover">
			<thead>
				<tr>
					<th>' . FH_Theme::__ (/*T[*/'Round 1 Score'/*]*/) . '</th>
					<th>' . FH_Theme::__ (/*T[*/'Round 2 Score'/*]*/) . '</th>
					<th>' . FH_Theme::__ (/*T[*/'Round 3 Score'/*]*/) . '</th>
					<th>' . FH_Theme::__ (/*T[*/'Total Score'/*]*/) . '</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>' . $score['round1'] . '</td>
					<td>' . $score['round2'] . '</td>
					<td>' . $score['round3'] . '</td>
					<td>' . $score['total'] . '</td>
				</tr>
			</tbody>
		</table>
	</div>';

			$out .= '</fieldset>' . "\n";

			$out .= '<fieldset>' . "\n\t" . '<legend>' . htmlentities ($this->get ('name')) . ' ' . FH_Theme::__ (/*T[*/'Conversations'/*]*/) . '</legend>' . "\n";

			if (isset ($all_states[FH_Game::ROUND1_BEGIN]['data']['questions']))
				foreach ($all_states[FH_Game::ROUND1_BEGIN]['data']['questions'] as $character_id => $questions) {
					$character = new FH_Character ($game->scenario ('path'), $character_id);
					$conversation = new FH_Conversation ($character);

					$out .= '<h4>' . $character->get ('name') . '</h4>' . "\n";
					$out .= $conversation->render ($questions, FALSE);
					}

			$out .= '</fieldset>' . "\n";

			$out .= '<fieldset>' . "\n\t" . '<legend>' . FH_Theme::__ (/*T[*/'Hints'/*]*/) . '</legend>' . "\n";

			$hints = $game->scenario ('hints');
			if (!empty ($hints)) {
				$out .= '<div class="sd-hints-render">' . "\n";
				foreach ($hints as $hint) {
					$out .= '<div class="sd-hint-render ' . ($score > $hint['hint_threshold'] ? 'sd-hint-visited' : 'sd-hint-missed') . '">' . $hint['hint_content'] . '</div>' . "\n";
					}
				$out .= '</div>' . "\n";
				}

			$out .= '</fieldset>' . "\n";

			$out .= '</div>' . "\n";

			if (!$echo) return $out;
			echo $out;
			}
		else {
			$report = [];

			if (!class_exists ('clsTinyButStrong')) {
				include (__DIR__ . '/tbs/tbs_class.php');
				include (__DIR__ . '/tbs/plugins/tbs_plugin_opentbs.php');
				}

			$tbs = new clsTinyButStrong ();
			$tbs->Plugin(TBS_INSTALL, OPENTBS_PLUGIN);

			$tbs->LoadTemplate(dirname (__DIR__) . '/assets/docx/salesdrive-report.docx', OPENTBS_ALREADY_UTF8);

			$all_states = $this->get ('state');
			$game = new FH_Game ((int) $this->data['game']);
			$products = new FH_List ('FH_Product', $game->scenario ('path'));
			$players = new FH_List ('FH_Player', [ sprintf ('game=%d', $this->data['game']) ]);
			$players->sort ('name');

			$report['game_name'] = $game->get ('name');
			$report['game_date'] = date ('d-m-Y');
			$report['player_name'] = htmlentities ($this->get ('name'));
			$score[] = [
				'round1'	=> $this->get ('score', 'round1'),
				'round2'	=> $this->get ('score', 'round2'),
				'round3'	=> $this->get ('score', 'round3'),
				'total'		=> $this->get ('score')
				];

			$tbs->MergeBlock ('score', $score);


			$offers = [];
			$final_quotations = $this->get ('quotations');
#			if (isset ($all_states[FH_Game::ROUND4_BEGIN]['data']['negotiation'][FH_Game::NEGOTIATION_3]))
#				foreach ($all_states[FH_Game::ROUND4_BEGIN]['data']['negotiation'][FH_Game::NEGOTIATION_3] as $quotation_id => $quotation)
				foreach ($final_quotations as $quotation_id => $quotation)
					$offers[] = [ 'description' => $this->quotation ($quotation, 'docx') ];

			$tbs->MergeBlock ('offers', $offers);

			$conversations = [];

			if (isset ($all_states[FH_Game::ROUND1_BEGIN]['data']['questions']))
				foreach ($all_states[FH_Game::ROUND1_BEGIN]['data']['questions'] as $character_id => $questions) {
					$character = new FH_Character ($game->scenario ('path'), $character_id);
					$conversation = new FH_Conversation ($character);

					$conversations = array_merge ($conversations, $conversation->docx_render ($questions));
					}

			$tbs->MergeBlock ('conversation', $conversations);


			$hints = [];
			$_hints = $game->scenario ('hints');
			if (!empty ($_hints)) {
				foreach ($_hints as $_hint) {
					$hints[] = [
							'description'	=> $_hint['hint_content'],
							'visited'	=> $score[0]['total'] > $_hint['hint_threshold']
							];
					}
				}
			$tbs->MergeBlock ('hints', $hints);

			$market = $game->get ('market');
			$this->market = $market[$this->ID];
			$this->computed = [];

			$tbs_products = [];
			$quoted_price = [];
			foreach ($products->get () as $product) {
				$qualities = $product->get ('quality');
				foreach ($qualities as $quality_slug => $quality_data) {
					$tbs_product = [
						'name'	=> $product->get ('name'),
						'type'	=> $quality_data['name']
						];
					
					foreach (FH_Product::$A as $key => $name) {
						$computed = $this->compute ($product, $key);
						$tbs_product[$key] = $computed[$quality_slug];

						if ($key == 'quoted_unit_price')
							$quoted_price[$product->get ()] = $computed;
						}

					$tbs_products[] = $tbs_product;
					}
				}

			$tbs->MergeBlock ('product', $tbs_products);

			$report['income'] = $this->compute ('income');
			$report['total_cost'] = $this->compute ('total_cost');
			$report['global_margin_percent'] = $this->compute ('global_margin_rel');
			$report['global_margin'] = $this->compute ('global_margin_abs');

			$titles = [];
			$perceived_costs = [];
			foreach ($players->get () as $player){
				$perceived_costs[$player->get()] = $player->compute ('perceived_cost');
				}

			$chart_no = 1;
			foreach ($products->get () as $product) {
				$qualities = $product->get ('quality');
				foreach ($qualities as $quality_slug => $quality_data) {
					$chart = [['name' => $product->get ('name') . ' / ' . $quality_data['name'], 'delete' => 0]];
					$tbs->MergeBlock ('chart_' . $chart_no, $chart);


					$chart_values = [];
					foreach ($players->get () as $player) {
						$chart_values[0][] = htmlentities ($player->get ('name'));
						$chart_values[1][] = (int) $market[$player->get ()][$product->get ()][$quality_slug];
						/*
						$charts[$product->get ()][$quality_slug][] = [
							$player->get ('name') . "\n" . 
								'Q' . ': ' . ((int) $market[$player->get ()][$product->get ()][$quality_slug]) . $quality_data['unit_type'] . 
								' P' . ': ' . $quoted_price[$product->get ()][$quality_slug] . $game->scenario ('currency') .
								' PV' . ': ' . $perceived_costs[$player->get ()][$product->get ()][$quality_slug] . $game->scenario ('currency'),
							(int) $market[$player->get ()][$product->get ()][$quality_slug]
							];
						*/
						}
					$tbs->PlugIn (OPENTBS_CHART, 'Chart ' . $chart_no, 'Sales', $chart_values);

					$chart_no ++;
					}
				}
			for ( ;$chart_no < 4; $chart_no++) {
				$chart = [['name' => '', 'delete' => 1]];
				$tbs->MergeBlock ('chart_' . $chart_no, $chart);
				}


			#$tbs->MergeBlock ('chart', $charts);
			$tbs->MergeField ('report', $report);

#			$tbs->Plugin(OPENTBS_DEBUG_XML_SHOW);
			$filename = vsprintf ('report_%s_%s.docx', [date ('dmy'), $this->get ()]);
			$path = dirname (__DIR__) . '/assets/reports';
			if ($echo == FALSE) {
				$tbs->Show (OPENTBS_FILE, $path . '/' . $filename);
				return [ 'path' => $path, 'file' => $filename ];
				}
			$tbs->Show (OPENTBS_DOWNLOAD, $filename);
			}
		}

	public function report_data () {
		$all_states = $this->get ('state');
		$game = new FH_Game ((int) $this->data['game']);
		$products = new FH_List ('FH_Product', $game->scenario ('path'));
		$players = new FH_List ('FH_Player', [ sprintf ('game=%d', $this->data['game']) ]);
		$players->sort ('name');

		$data = [
			'scores'	=> [
					'player_name'	=> $this->get ('name'),
					'round1'	=> $this->get ('score', 'round1'),
					'round2'	=> $this->get ('score', 'round2'),
					'round3'	=> $this->get ('score', 'round3'),
					'total'		=> $this->get ('score')
					],
			'finals'	=> [
					'player_name'	=> $this->get ('name'),
					'offers'	=> []
					],
			'conversations'	=> [
					'player_name'	=> $this->get ('name'),
					'conversation'	=> []
					],
			'hints'		=> [
					'player_name'	=> $this->get ('name'),
					'hint'		=> []
					],
			'products'	=> [
					'player_name'	=> $this->get ('name'),
					'player'	=> []
					],
			'overview'	=> [
					'player_name'		=> $this->get ('name'),
					'income'		=> $this->compute ('income'),
					'total_cost'		=> $this->compute ('total_cost'),
					'global_margin_percent' => $this->compute ('global_margin_rel'),
					'global_margin'		=> $this->compute ('global_margin_abs')
					]
			];

		
		$final_quotations = $this->get ('quotations');
		foreach ($final_quotations as $quotation_id => $quotation)
			$data['finals']['offers'][] = [ 'description' => $this->quotation ($quotation, 'docx') ];

		$conversations = [];	
		if (isset ($all_states[FH_Game::ROUND1_BEGIN]['data']['questions']))
			foreach ($all_states[FH_Game::ROUND1_BEGIN]['data']['questions'] as $character_id => $questions) {
				$character = new FH_Character ($game->scenario ('path'), $character_id);
				$conversation = new FH_Conversation ($character);

				$conversations = array_merge ($conversations, $conversation->docx_render ($questions));
				}
		$data['conversations']['conversation'] = $conversations;
		unset ($conversations);

		$hints = $game->scenario ('hints');
		if (!empty ($hints)) {
			foreach ($hints as $hint) {
				$data['hints']['hint'][] = [
						'description'	=> $hint['hint_content'],
						'visited'	=> $data['scores']['total'] > $hint['hint_threshold']
						];
				}
			}

		$market = $game->get ('market');
		$this->market = $market[$this->ID];
		$this->computed = [];

		$quoted_price = [];
		foreach ($products->get () as $product) {
			$qualities = $product->get ('quality');
			foreach ($qualities as $quality_slug => $quality_data) {
				$tbs_product = [
					'name'		=> $product->get ('name'),
					'type'		=> $quality_data['name']
					];
				
				foreach (FH_Product::$A as $key => $name) {
					$computed = $this->compute ($product, $key);
					$tbs_product[$key] = $computed[$quality_slug];

					if ($key == 'quoted_unit_price')
						$quoted_price[$product->get ()] = $computed;
					}

				$data['products']['player'][] = $tbs_product;
				}
			}

		return $data;
		}

	public static function scramble ($length = 6) {
		global $wpdb;

		$searching = TRUE;
		$alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890';

		while ($searching) {
			$scramble = '';
			for ($count = 0; $count < $length; $count ++)
				$scramble .= $alphabet[rand(0, strlen($alphabet) - 1)];
			
			$searching = $wpdb->get_var($wpdb->prepare ('select count(*) from `' . $wpdb->prefix . static::$T . '` where password=%s', $scramble)) ? TRUE : FALSE;
			}

		return $scramble;
		}

	public static function logout () {
		$storage = new FH_Storage ();
		$storage->set ('player', null);
		}
	}
?>

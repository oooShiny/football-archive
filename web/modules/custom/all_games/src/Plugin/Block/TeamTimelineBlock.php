<?php

namespace Drupal\all_games\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\node\Entity\Node;

/**
 * Provides a 'Team Timeline' Block.
 *
 * @Block(
 *   id = "team_timeline_block",
 *   admin_label = @Translation("Team Timeline block"),
 *   category = @Translation("Football"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"))
 *   }
 * )
 */

class TeamTimelineBlock extends BlockBase {

  public function build() {
    $node = $this->getContextValue('node');
    $games = $this->get_games($node->label());
    $videos = $this->get_videos($node->id(), $games);
    return [
      '#theme' => 'team_timeline',
      '#videos' => $videos,
    ];
  }

  public function get_games($team) {
    $week_order = [
      '1' => 1,
      '2' => 2,
      '3' => 3,
      '4' => 4,
      '5' => 5,
      '6' => 6,
      '7' => 7,
      '8' => 8,
      '9' => 9,
      '10' => 10,
      '11' => 11,
      '12' => 12,
      '13' => 13,
      '14' => 14,
      '15' => 15,
      '16' => 16,
      '17' => 17,
      '18' => 18,
      '19' => 19,
      'Wildcard' => 20,
      'Division' => 21,
      'Conference' => 22,
      'Superbowl' => 23,
      'SuperBowl' => 23,
    ];
    $games = [];
    // Get all games from custom table.
    $database = \Drupal::database();
    $query = $database->select('all_games', 'g')
      ->fields('g', ['schedule_season', 'schedule_week', 'team_home', 'team_away']);
    $orGroup = $query->orConditionGroup()
      ->condition('g.team_home', $team)
      ->condition('g.team_away', $team);
    $query->condition($orGroup);
    $result = $query->execute();
    foreach ($result as $g) {
      if (is_numeric($g->schedule_week)) {
        $week = 'Week ' . $g->schedule_week;
      }
      else {
        $rename = [
          'Wildcard' => 'Wildcard',
          'Division' => 'Divisional',
          'Conference' => 'Conference Championship',
          'Superbowl' => 'Super Bowl',
          'SuperBowl' => 'Super Bowl',
        ];
        $week = $rename[$g->schedule_week];
      }
      $games[$g->schedule_season]['games'][$week_order[$g->schedule_week]] = [
        'title' => $week . ': ' . $g->team_away . ' @ ' . $g->team_home,
      ];
    }
    return $games;
  }
  public function get_videos($team, $games) {
    $nids = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', ['game_video', 'non_game_video'], 'IN')
      ->execute();

    foreach (Node::loadMultiple($nids) as $video) {
      // Check for non-game videos related to the current team.
      if ($video->hasField('field_team_s_involved')) {
        foreach ($video->field_team_s_involved->referencedEntities() as $t) {
          if ($t->id() == $team) {
            $games[$video->get('field_season')->value]['features'][] = [
              'id' => $video->id(),
              'title' => $video->label(),
            ];
          }
        }
      }
      // Check for game videos related to the current team.
      elseif ($video->hasField('field_teams_involved')) {
        foreach ($video->field_teams_involved->referencedEntities() as $t) {
          if ($t->id() == $team) {
            $week = $video->get('field_week')->value;
            $games[$video->get('field_season')->value]['games'][$week] = [
              'id' => $video->id(),
              'title' => substr($video->label(), 4),
            ];
            ksort($games[$video->get('field_season')->value]['games']);
          }
        }
      }
    }
    ksort($games);
    return $games;
  }
}

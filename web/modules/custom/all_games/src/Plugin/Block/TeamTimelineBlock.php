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
      $games[$g->schedule_season]['games'][$g->schedule_week] = [
        'title' => 'Week ' . $g->schedule_week . ': ' . $g->team_away . ' @ ' . $g->team_home,
      ];
    }
    return $games;
  }
  public function get_videos($team, $games) {
    $week_order = [
      1 => 'Week 1',
      2 => 'Week 2',
      3 => 'Week 3',
      4 => 'Week 4',
      5 => 'Week 5',
      6 => 'Week 6',
      7 => 'Week 7',
      8 => 'Week 8',
      9 => 'Week 9',
      11 => 'Week 11',
      12 => 'Week 12',
      13 => 'Week 13',
      14 => 'Week 14',
      15 => 'Week 15',
      16 => 'Week 16',
      17 => 'Week 17',
      18 => 'Week 18',
      19 => 'Wildcard',
      20 => 'Divisional',
      21 => 'Conference Championship',
      22 => 'Super Bowl',

    ];
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

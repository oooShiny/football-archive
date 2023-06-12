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
    $videos = $this->get_videos($node->id());
    return [
      '#theme' => 'team_timeline',
      '#videos' => $videos,
    ];
  }

  public function get_videos($team) {
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
    $video_nodes = [];
    foreach (Node::loadMultiple($nids) as $video) {
      // Check for non-game videos related to the current team.
      if ($video->hasField('field_team_s_involved')) {
        foreach ($video->field_team_s_involved->referencedEntities() as $t) {
          if ($t->id() == $team) {
            $video_nodes[$video->get('field_season')->value]['features'][] = [
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
            $video_nodes[$video->get('field_season')->value]['games'][$week] = [
              'id' => $video->id(),
              'title' => $video->label(),
            ];
            ksort($video_nodes[$video->get('field_season')->value]['games']);
          }
        }
      }
    }
    ksort($video_nodes);
    return $video_nodes;
  }
}

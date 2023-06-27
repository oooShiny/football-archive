<?php

namespace Drupal\all_games\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\node\Entity\Node;

/**
 * Provides a 'Total Hours' Block.
 *
 * @Block(
 *   id = "total_hours_block",
 *   admin_label = @Translation("Total Hours block"),
 *   category = @Translation("Football"),
 * )
 */

class TotalHoursBlock extends BlockBase {

  public function build() {
    $time = $this->get_times();
    return [
      '#theme' => 'total_hours',
      '#time' => $time,
    ];
  }
  public function get_times() {
    $total_time = [
      'watched' => [
        'game' => 0,
        'feature' => 0,
      ],
      'total' => [
        'game' => 0,
        'feature' => 0,
      ],
    ];
    $nids = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', ['game_video', 'non_game_video'], 'IN')
      ->condition('status', 1)
      ->execute();

    foreach (Node::loadMultiple($nids) as $video) {
      $watch_time = $video->get('field_total_watch_time')->value;
      $vid_length = $video->get('field_video_length')->value;
      if ($video->hasField('field_team_s_involved')) {
        $total_time['watched']['feature']+= $watch_time;
        $total_time['total']['feature']+= $vid_length;
      }
      elseif ($video->hasField('field_teams_involved')) {
        $total_time['watched']['game']+= $watch_time;
        $total_time['total']['game']+= $vid_length;
      }
    }
    return $total_time;
  }
}

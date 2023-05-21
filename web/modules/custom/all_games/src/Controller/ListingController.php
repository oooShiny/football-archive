<?php

namespace Drupal\all_games\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;

/**
 * An example controller.
 */
class ListingController extends ControllerBase {

  /**
   * Returns a render-able array for a page.
   */
  public function content() {

    // Get all videos from Drupal.
    $videos = $this->get_video_nodes();

    // Get all games from custom table.
    $database = \Drupal::database();
    $query = $database->query("SELECT * FROM {all_games}");
    $games = [];
    foreach ($query->fetchAllAssoc('id') as $g) {
      if (!array_key_exists($g->schedule_season, $games) ||
        !array_key_exists('vids', $games[$g->schedule_season])) {
        $games[$g->schedule_season]['vids'] = 0;
      }
      // If video of this game exists, link it.
      if (array_key_exists($g->schedule_season, $videos) &&
        array_key_exists($g->schedule_week, $videos[$g->schedule_season]) &&
        array_key_exists($g->team_home, $videos[$g->schedule_season][$g->schedule_week])) {

        $gid = $videos[$g->schedule_season][$g->schedule_week][$g->team_home];
        $games[$g->schedule_season]['games'][$g->schedule_week][] = [
          'home_team' => $g->team_home,
          'home_score' => $g->score_home,
          'away_score' => $g->score_away,
          'away_team' => $g->team_away,
          'vid' => $gid,
        ];
        $games[$g->schedule_season]['vids'] += 1;
      } else {
        $games[$g->schedule_season]['games'][$g->schedule_week][] = [
          'home_team' => $g->team_home,
          'home_score' => $g->score_home,
          'away_score' => $g->score_away,
          'away_team' => $g->team_away,
          'vid' => NULL,
        ];
      }
    }

    $build = [
      '#theme' => 'all_games_list',
      '#all_games' => $games,
    ];

    return $build;
  }

  public function get_video_nodes() {
    $nids = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('type','game_video')
      ->execute();
    $video_nodes = [];
    foreach (Node::loadMultiple($nids) as $video) {
      if ($video->hasField('field_away_team') && !$video->field_away_team->isEmpty()) {
        $home = $video->field_away_team->entity->label();
        $video_nodes[$video->get('field_season')->value][$video->get('field_week')->value][$home] = $video->id();
      }
    }

    return $video_nodes;
  }

}

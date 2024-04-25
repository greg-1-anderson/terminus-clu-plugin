<?php

namespace Pantheon\TerminusClu\ServiceProviders\RepositoryProviders\GitLab;

use Pantheon\TerminusBuildTools\ServiceProviders\RepositoryProviders\GitLab\GitLabProvider as BuildToolsGitLabProvider;
use Pantheon\TerminusClu\ServiceProviders\RepositoryProviders\GitProvider;

class GitLabProvider extends BuildToolsGitLabProvider implements GitProvider {

  public function cloneRepository($target_project, $destination) {
    $gitlab_token = $this->token();
    $gitlab_url = $this->getGitLabUrl();
    $remote_url = "https://gitlab-ci-token:$gitlab_token@$gitlab_url/$target_project.git";
    $this->execWithRedaction("git clone {remote} $destination", ['remote' => $remote_url], ['remote' => $target_project]);
  }

  private function getMergeRequestDetails($target_project, $id) {
    if ($data = $this->api()
      ->request("api/v4/projects/" . urlencode($target_project) . "/merge_requests/$id", [], 'GET')) {
      return $data;
    }
    $this->logger->error("{id} is an invalid merge request IID.", ["id" => $id]);
  }

  private function getProjectDetails($target_project) {
    if ($data = $this->api()
      ->request("api/v4/projects/" . urlencode($target_project), [], 'GET')) {
      return $data;
    }
    $this->logger->error("{project} is an invalid project.", ["project" => $target_project]);
  }

  public function closePullRequest($target_project, $id) {
    $this->logger
      ->notice("Closing PR {id} on {project}", [
        'id' => $id,
        'project' => $target_project,
      ]);
    $pr_data = $this->getMergeRequestDetails($target_project, $id);
    $id = $pr_data['id'];
    $iid = $pr_data['iid'];
    if ($data = $this->api()
      ->request("api/v4/projects/" . urlencode($target_project) . "/merge_requests/$iid", [
        'state_event' => 'close',
      ], 'PUT')) {
      $this->logger->notice("Merge request {id} has been closed.", ["id" => $id]);
      return $data;
    }
    $this->logger->error("Failed to close merge request {id}.", ["id" => $id]);
  }

  public function createPullRequest($target_project, $source_branch, $title, array $options = []) {
    $project_id = $this->getProjectID($target_project);
    $postData = [
      'id' => $project_id,
      'title' => $title,
      'source_branch' => $source_branch,
    ];

    if (!empty($options['target'])) {
      $postData['target_branch'] = $options['target'];
      unset($options['target']);
    } else {
      $project_details = $this->getProjectDetails($target_project);
      $postData['target_branch'] = $project_details['default_branch'];
    }

    // GitLab doesn't have reviewers
    // See https://docs.gitlab.com/ee/api/merge_requests.html#create-mr
    unset($options['reviewers']);

    $postData['remove_source_branch'] = !empty($options['close']) ? TRUE : FALSE;
    unset($options['close']);
    $postData += array_filter($options);

    $this->logger
      ->notice("Creating merge request on {project} for {source}", [
        "project" => $target_project,
        "source" => $source_branch,
      ]);
    if ($data =
      $this->api()
        ->request("api/v4/projects/" . urlencode($target_project) . "/merge_requests", $postData, 'POST')) {
      $this->logger
        ->notice("Merge request #{id} \"{title}\" created successfully: {url}", [
          "id" => $data['iid'],
          "title" => $data['title'],
          "url" => $data['web_url']
        ]);
      return $data;
    }
    $this->logger
      ->error("Creating merge request failed");
    return FALSE;
  }

}
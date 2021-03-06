<?php

/*
 * Copyright 2012 Facebook, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

final class DifferentialChangesetListView extends AphrontView {

  private $changesets = array();
  private $references = array();
  private $inlineURI;
  private $renderURI = '/differential/changeset/';
  private $whitespace;

  private $standaloneURI;
  private $leftRawFileURI;
  private $rightRawFileURI;

  private $user;
  private $symbolIndexes = array();
  private $repository;
  private $diff;
  private $vsMap = array();

  public function setChangesets($changesets) {
    $this->changesets = $changesets;
    return $this;
  }

  public function setInlineCommentControllerURI($uri) {
    $this->inlineURI = $uri;
    return $this;
  }

  public function setUser(PhabricatorUser $user) {
    $this->user = $user;
    return $this;
  }

  public function setRepository(PhabricatorRepository $repository) {
    $this->repository = $repository;
    return $this;
  }

  public function setDiff(DifferentialDiff $diff) {
    $this->diff = $diff;
    return $this;
  }

  public function setRenderingReferences(array $references) {
    $this->references = $references;
    return $this;
  }

  public function setSymbolIndexes(array $indexes) {
    $this->symbolIndexes = $indexes;
    return $this;
  }

  public function setRenderURI($render_uri) {
    $this->renderURI = $render_uri;
    return $this;
  }

  public function setWhitespace($whitespace) {
    $this->whitespace = $whitespace;
    return $this;
  }

  public function setVsMap(array $vs_map) {
    $this->vsMap = $vs_map;
    return $this;
  }

  public function getVsMap() {
    return $this->vsMap;
  }

  public function setStandaloneURI($uri) {
    $this->standaloneURI = $uri;
    return $this;
  }

  public function setRawFileURIs($l, $r) {
    $this->leftRawFileURI = $l;
    $this->rightRawFileURI = $r;
    return $this;
  }

  public function render() {
    require_celerity_resource('differential-changeset-view-css');

    $changesets = $this->changesets;

    Javelin::initBehavior('buoyant', array());

    $output = array();
    $mapping = array();
    foreach ($changesets as $key => $changeset) {
      $file = $changeset->getFilename();
      $class = 'differential-changeset';
      if (!$this->inlineURI) {
        $class .= ' differential-changeset-noneditable';
      }

      $ref = $this->references[$key];

      $detail = new DifferentialChangesetDetailView();

      $view_options = $this->renderViewOptionsDropdown(
        $detail,
        $ref,
        $changeset);

      $detail->setChangeset($changeset);
      $detail->addButton($view_options);
      $detail->setSymbolIndex(idx($this->symbolIndexes, $key));
      $detail->setVsChangesetID(idx($this->vsMap, $changeset->getID()));

      $uniq_id = celerity_generate_unique_node_id();
      $detail->appendChild(
        phutil_render_tag(
          'div',
          array(
            'id' => $uniq_id,
          ),
          '<div class="differential-loading">Loading...</div>'));
      $output[] = $detail->render();

      $mapping[$uniq_id] = $ref;
    }

    require_celerity_resource('aphront-tooltip-css');

    Javelin::initBehavior('differential-populate', array(
      'registry'    => $mapping,
      'whitespace'  => $this->whitespace,
      'uri'         => $this->renderURI,
    ));

    Javelin::initBehavior('differential-show-more', array(
      'uri' => $this->renderURI,
      'whitespace' => $this->whitespace,
    ));

    Javelin::initBehavior('differential-comment-jump', array());

    if ($this->inlineURI) {
      $undo_templates = $this->renderUndoTemplates();

      Javelin::initBehavior('differential-edit-inline-comments', array(
        'uri'             => $this->inlineURI,
        'undo_templates'  => $undo_templates,
        'stage'           => 'differential-review-stage',
      ));
    }

    return phutil_render_tag(
      'div',
      array(
        'class' => 'differential-review-stage',
        'id'    => 'differential-review-stage',
      ),
      implode("\n", $output));
  }

  /**
   * Render the "Undo" markup for the inline comment undo feature.
   */
  private function renderUndoTemplates() {
    $link = javelin_render_tag(
      'a',
      array(
        'href'  => '#',
        'sigil' => 'differential-inline-comment-undo',
      ),
      'Undo');

    $div = phutil_render_tag(
      'div',
      array(
        'class' => 'differential-inline-undo',
      ),
      'Changes discarded. '.$link);

    $content = '<th></th><td>'.$div.'</td>';
    $empty   = '<th></th><td></td>';

    $left = array($content, $empty);
    $right = array($empty, $content);

    return array(
      'l' => '<table><tr>'.implode('', $left).'</tr></table>',
      'r' => '<table><tr>'.implode('', $right).'</tr></table>',
    );
  }

  private function renderViewOptionsDropdown(
    DifferentialChangesetDetailView $detail,
    $ref,
    DifferentialChangeset $changeset) {

    $meta = array();

    $qparams = array(
      'ref'         => $ref,
      'whitespace'  => $this->whitespace,
    );

    if ($this->standaloneURI) {
      $uri = new PhutilURI($this->standaloneURI);
      $uri->setQueryParams($uri->getQueryParams() + $qparams);
      $meta['standaloneURI'] = (string)$uri;
    }

    $repository = $this->repository;
    if ($repository) {
      $meta['diffusionURI'] = $repository->getDiffusionBrowseURIForPath(
        $changeset->getAbsoluteRepositoryPath($this->diff, $repository));
    }

    $change = $changeset->getChangeType();

    if ($this->leftRawFileURI) {
      if ($change != DifferentialChangeType::TYPE_ADD) {
        $uri = new PhutilURI($this->leftRawFileURI);
        $uri->setQueryParams($uri->getQueryParams() + $qparams);
        $meta['leftURI'] = (string)$uri;
      }
    }

    if ($this->rightRawFileURI) {
      if ($change != DifferentialChangeType::TYPE_DELETE &&
          $change != DifferentialChangeType::TYPE_MULTICOPY) {
        $uri = new PhutilURI($this->rightRawFileURI);
        $uri->setQueryParams($uri->getQueryParams() + $qparams);
        $meta['rightURI'] = (string)$uri;
      }
    }

    $user = $this->user;
    if ($user && $repository) {
      $path = ltrim(
        $changeset->getAbsoluteRepositoryPath($this->diff, $repository),
        '/');
      $line = 1; // TODO: get first changed line
      $editor_link = $user->loadEditorLink($path, $line, $repository);
      if ($editor_link) {
        $meta['editor'] = $editor_link;
      } else {
        $meta['editorConfigure'] = '/settings/page/preferences/';
      }
    }

    $meta['containerID'] = $detail->getID();

    Javelin::initBehavior(
      'differential-dropdown-menus',
      array());

    return javelin_render_tag(
      'a',
      array(
        'class'   => 'button small grey',
        'meta'    => $meta,
        'href'    => idx($meta, 'detailURI', '#'),
        'target'  => '_blank',
        'sigil'   => 'differential-view-options',
      ),
      "View Options \xE2\x96\xBC");
  }

}

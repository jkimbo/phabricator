<?php

final class PhabricatorTimelineEventView extends AphrontView {

  private $userHandle;
  private $title;
  private $icon;
  private $color;
  private $classes = array();
  private $contentSource;
  private $dateCreated;
  private $viewer;
  private $anchor;
  private $isEditable;
  private $isEdited;
  private $transactionPHID;

  public function setTransactionPHID($transaction_phid) {
    $this->transactionPHID = $transaction_phid;
    return $this;
  }

  public function getTransactionPHID() {
    return $this->transactionPHID;
  }

  public function setIsEdited($is_edited) {
    $this->isEdited = $is_edited;
    return $this;
  }

  public function getIsEdited() {
    return $this->isEdited;
  }


  public function setIsEditable($is_editable) {
    $this->isEditable = $is_editable;
    return $this;
  }

  public function getIsEditable() {
    return $this->isEditable;
  }

  public function setViewer(PhabricatorUser $viewer) {
    $this->viewer = $viewer;
    return $this;
  }

  public function getViewer() {
    return $this->viewer;
  }

  public function setDateCreated($date_created) {
    $this->dateCreated = $date_created;
    return $this;
  }

  public function getDateCreated() {
    return $this->dateCreated;
  }

  public function setContentSource(PhabricatorContentSource $content_source) {
    $this->contentSource = $content_source;
    return $this;
  }

  public function getContentSource() {
    return $this->contentSource;
  }

  public function setUserHandle(PhabricatorObjectHandle $handle) {
    $this->userHandle = $handle;
    return $this;
  }

  public function setAnchor($anchor) {
    $this->anchor = $anchor;
    return $this;
  }

  public function setTitle($title) {
    $this->title = $title;
    return $this;
  }

  public function addClass($class) {
    $this->classes[] = $class;
    return $this;
  }

  public function setIcon($icon) {
    $this->icon = $icon;
    return $this;
  }

  public function setColor($color) {
    $this->color = $color;
    return $this;
  }

  public function render() {
    $content = $this->renderChildren();

    $title = $this->title;
    if (($title === null) && !strlen($content)) {
      $title = '';
    }

    $extra = array();
    $xaction_phid = $this->getTransactionPHID();

    if ($this->getIsEdited()) {
      $extra[] = javelin_render_tag(
        'a',
        array(
          'href'  => '/transactions/history/'.$xaction_phid.'/',
          'sigil' => 'workflow',
        ),
        pht('Edited'));
    }

    if ($this->getIsEditable()) {
      $extra[] = javelin_render_tag(
        'a',
        array(
          'href'  => '/transactions/edit/'.$xaction_phid.'/',
          'sigil' => 'workflow transaction-edit',
        ),
        pht('Edit'));
    }

    $source = $this->getContentSource();
    if ($source) {
      $extra[] = id(new PhabricatorContentSourceView())
        ->setContentSource($source)
        ->setUser($this->getViewer())
        ->render();
    }

    if ($this->getDateCreated()) {
      $date = phabricator_datetime(
        $this->getDateCreated(),
        $this->getViewer());
      if ($this->anchor) {
        Javelin::initBehavior('phabricator-watch-anchor');

        $anchor = id(new PhabricatorAnchorView())
          ->setAnchorName($this->anchor)
          ->render();

        $date = $anchor.phutil_render_tag(
            'a',
            array(
              'href' => '#'.$this->anchor,
            ),
            $date);
      }
      $extra[] = $date;
    }

    $extra = implode(' &middot; ', $extra);
    if ($extra) {
      $extra = phutil_render_tag(
        'span',
        array(
          'class' => 'phabricator-timeline-extra',
        ),
        $extra);
    }

    if ($title !== null || $extra !== null) {
      $title_classes = array();
      $title_classes[] = 'phabricator-timeline-title';

      $icon = null;
      if ($this->icon) {
        $title_classes[] = 'phabricator-timeline-title-with-icon';

        $icon = phutil_render_tag(
          'span',
          array(
            'class' => 'phabricator-timeline-icon-fill',
          ),
          phutil_render_tag(
            'span',
            array(
              'class' => 'phabricator-timeline-icon sprite-icon '.
                         'action-'.$this->icon.'-white',
            ),
            ''));
      }

      $title = phutil_render_tag(
        'div',
        array(
          'class' => implode(' ', $title_classes),
        ),
        $title.$extra);

      $title = $icon.$title;
    }

    $wedge = phutil_render_tag(
      'div',
      array(
        'class' => 'phabricator-timeline-wedge phabricator-timeline-border',
      ),
      '');

    $image_uri = $this->userHandle->getImageURI();
    $image = phutil_render_tag(
      'div',
      array(
        'style' => 'background-image: url('.$image_uri.')',
        'class' => 'phabricator-timeline-image',
      ),
      '');

    $content_classes = array();
    $content_classes[] = 'phabricator-timeline-content';

    $classes = array();
    $classes[] = 'phabricator-timeline-event-view';
    $classes[] = 'phabricator-timeline-border';
    if ($content) {
      $classes[] = 'phabricator-timeline-major-event';
      $content = phutil_render_tag(
        'div',
        array(
          'class' => implode(' ', $content_classes),
        ),
        phutil_render_tag(
          'div',
          array(
            'class' => 'phabricator-timeline-inner-content',
          ),
          $title.
          phutil_render_tag(
            'div',
            array(
              'class' => 'phabricator-timeline-core-content',
            ),
            $content)));
      $content = $image.$wedge.$content;
    } else {
      $classes[] = 'phabricator-timeline-minor-event';
      $content = phutil_render_tag(
        'div',
        array(
          'class' => implode(' ', $content_classes),
        ),
        $image.$wedge.$title);
    }

    $outer_classes = $this->classes;
    $outer_classes[] = 'phabricator-timeline-shell';
    if ($this->color) {
      $outer_classes[] = 'phabricator-timeline-'.$this->color;
    }

    $sigil = null;
    $meta = null;
    if ($this->getTransactionPHID()) {
      $sigil = 'transaction';
      $meta = array(
        'phid' => $this->getTransactionPHID(),
        'anchor' => $this->anchor,
      );
    }

    return javelin_render_tag(
      'div',
      array(
        'class' => implode(' ', $outer_classes),
        'id' => $this->anchor ? 'anchor-'.$this->anchor : null,
        'sigil' => $sigil,
        'meta' => $meta,
      ),
      phutil_render_tag(
        'div',
        array(
          'class' => implode(' ', $classes),
        ),
        $content));
  }

}

jQuery(window).on('load', function () {
    const $header = jQuery('.l-header');
    const $mainContent = jQuery('.l-mainContent');
    const $backBoard = jQuery('.backBoard');
    const $emptySpacer = jQuery('.l-empty');
    const $searchPanel = jQuery('#header-search-panel');
    const $searchToggleButtons = jQuery('.js-header-search-toggle');
    const $searchCloseButtons = jQuery('[data-search-close]');
    let searchHideTimer = null;
    let lastSearchTrigger = null;

    function syncBackgroundHeight() {
        if (!$mainContent.length || !$backBoard.length) {
            return;
        }

        $backBoard.css('height', $mainContent.outerHeight() + 'px');
    }

    function syncHeaderSpacing() {
        if (!$header.length) {
            return;
        }

        const headerHeight = $header.outerHeight();

        if ($emptySpacer.length) {
            $emptySpacer.css('height', headerHeight + 'px');
        }

        if ($searchPanel.length) {
            $searchPanel.css('top', headerHeight + 10 + 'px');
        }
    }

    function closeSearchPanel(restoreFocus) {
        if (!$searchPanel.length) {
            return;
        }

        if (searchHideTimer) {
            window.clearTimeout(searchHideTimer);
        }

        $searchPanel.removeClass('slideInput__is-open').attr('aria-hidden', 'true');
        $searchToggleButtons.attr('aria-expanded', 'false');

        searchHideTimer = window.setTimeout(function () {
            $searchPanel.prop('hidden', true);
            searchHideTimer = null;
        }, 300);

        if (restoreFocus && lastSearchTrigger) {
            jQuery(lastSearchTrigger).trigger('focus');
        }
    }

    function openSearchPanel(triggerButton) {
        if (!$searchPanel.length) {
            return;
        }

        if (searchHideTimer) {
            window.clearTimeout(searchHideTimer);
            searchHideTimer = null;
        }

        lastSearchTrigger = triggerButton || null;

        $searchPanel.prop('hidden', false).attr('aria-hidden', 'false');
        $searchToggleButtons.attr('aria-expanded', 'true');

        window.requestAnimationFrame(function () {
            $searchPanel.addClass('slideInput__is-open');
        });

        const $keywordField = $searchPanel.find('[name="s"]').first();

        if ($keywordField.length) {
            $keywordField.trigger('focus');
        }
    }

    function initFeaturedCarousels() {
        jQuery('[data-featured-carousel]').each(function () {
            const $carousel = jQuery(this);
            const $viewport = $carousel.find('[data-carousel-viewport]');
            const $track = $carousel.find('[data-carousel-track]');
            const $originalSlides = $track.children('[data-carousel-slide]');

            if (!$originalSlides.length) {
                return;
            }

            const $dots = $carousel.find('[data-carousel-dot]');
            const $prevButton = $carousel.find('[data-carousel-prev]');
            const $nextButton = $carousel.find('[data-carousel-next]');
            const preferredStartIndex = Number($carousel.attr('data-carousel-start-index'));
            const slideCount = $originalSlides.length;
            const canLoop = slideCount > 1;
            const middleSetStartIndex = canLoop ? slideCount : 0;
            let currentIndex = Number.isNaN(preferredStartIndex) ? 0 : preferredStartIndex;
            let currentRenderIndex = currentIndex;

            function normalizeIndex(index) {
                return (index + slideCount) % slideCount;
            }

            function buildCloneSet($slides, groupLabel) {
                return $slides.map(function (slideIndex) {
                    const $clone = jQuery(this).clone();

                    $clone
                        .addClass('is-clone')
                        .attr({
                            id: ($clone.attr('id') || 'featured-slide') + '-clone-' + groupLabel + '-' + slideIndex,
                            'aria-hidden': 'true',
                            'aria-current': 'false'
                        })
                        .find('a')
                        .attr('tabindex', '-1');

                    return $clone.get(0);
                });
            }

            function getMiddleRenderIndex(logicalIndex) {
                return logicalIndex + middleSetStartIndex;
            }

            function getStableRenderIndex(renderIndex) {
                if (!canLoop) {
                    return renderIndex;
                }

                if (renderIndex < middleSetStartIndex) {
                    return renderIndex + slideCount;
                }

                if (renderIndex >= middleSetStartIndex + slideCount) {
                    return renderIndex - slideCount;
                }

                return renderIndex;
            }

            if (canLoop) {
                $track.prepend(buildCloneSet($originalSlides, 'before'));
                $track.append(buildCloneSet($originalSlides, 'after'));
                currentRenderIndex = getMiddleRenderIndex(currentIndex);
            }

            let $renderSlides = $track.children('[data-carousel-slide]');

            function syncTrackInsets(referenceIndex) {
                if (!$viewport.length || !$renderSlides.length) {
                    return;
                }

                const viewportWidth = $viewport.get(0).clientWidth;
                const $referenceSlide = $renderSlides.eq(referenceIndex);
                const slideWidth = $referenceSlide.length ? $referenceSlide.outerWidth() : 0;
                const sideInset = Math.max((viewportWidth - slideWidth) / 2, 0);

                $track.css({
                    paddingLeft: sideInset + 'px',
                    paddingRight: sideInset + 'px'
                });
            }

            function syncFeaturedCarouselState(renderIndex, logicalIndex) {
                $renderSlides
                    .removeClass('is-active')
                    .attr('aria-current', 'false');

                $renderSlides.eq(renderIndex)
                    .addClass('is-active');

                $originalSlides.each(function (slideIndex) {
                    jQuery(this).attr('aria-current', slideIndex === logicalIndex ? 'true' : 'false');
                });

                $dots.each(function (dotIndex) {
                    const isActive = dotIndex === logicalIndex;

                    jQuery(this)
                        .toggleClass('is-active', isActive)
                        .attr('aria-current', isActive ? 'true' : 'false');
                });

            }

            function setFeaturedCarouselAnimationEnabled(shouldAnimate) {
                $track.toggleClass('is-no-transition', !shouldAnimate);
            }

            function positionFeaturedCarousel(renderIndex) {
                if ($track.length && $viewport.length) {
                    const viewportElement = $viewport.get(0);
                    const trackElement = $track.get(0);
                    const activeSlideElement = $renderSlides.eq(renderIndex).get(0);

                    if (viewportElement && trackElement && activeSlideElement) {
                        syncTrackInsets(renderIndex);
                        const rawOffset = activeSlideElement.offsetLeft + (activeSlideElement.offsetWidth / 2) - (viewportElement.clientWidth / 2);
                        const maxOffset = Math.max(trackElement.scrollWidth - viewportElement.clientWidth, 0);
                        const clampedOffset = Math.min(Math.max(rawOffset, 0), maxOffset);

                        $track.css('transform', 'translateX(-' + clampedOffset + 'px)');
                    }
                }
            }

            function jumpToRenderIndex(renderIndex) {
                setFeaturedCarouselAnimationEnabled(false);
                currentRenderIndex = renderIndex;
                syncFeaturedCarouselState(currentRenderIndex, currentIndex);
                positionFeaturedCarousel(currentRenderIndex);
            }

            function syncFeaturedCarouselLoop() {
                const stableRenderIndex = getStableRenderIndex(currentRenderIndex);

                if (stableRenderIndex === currentRenderIndex) {
                    return;
                }

                jumpToRenderIndex(stableRenderIndex);
            }

            function updateFeaturedCarousel(nextIndex) {
                if (!canLoop) {
                    setFeaturedCarouselAnimationEnabled(true);
                    currentIndex = normalizeIndex(nextIndex);
                    currentRenderIndex = currentIndex;
                    syncFeaturedCarouselState(currentRenderIndex, currentIndex);
                    positionFeaturedCarousel(currentRenderIndex);
                    return;
                }

                const previousIndex = currentIndex;
                currentIndex = normalizeIndex(nextIndex);
                setFeaturedCarouselAnimationEnabled(true);

                if (nextIndex === previousIndex + 1) {
                    currentRenderIndex += 1;
                } else if (nextIndex === previousIndex - 1) {
                    currentRenderIndex -= 1;
                } else {
                    currentRenderIndex = getMiddleRenderIndex(currentIndex);
                }

                syncFeaturedCarouselState(currentRenderIndex, currentIndex);
                positionFeaturedCarousel(currentRenderIndex);
            }

            if (currentIndex < 0 || currentIndex >= slideCount) {
                currentIndex = 0;
                currentRenderIndex = canLoop ? getMiddleRenderIndex(currentIndex) : 0;
            }

            $track.on('transitionend', function (event) {
                const originalEvent = event.originalEvent;

                if (event.target !== $track.get(0)) {
                    return;
                }

                if (originalEvent && 'transform' !== originalEvent.propertyName) {
                    return;
                }

                syncFeaturedCarouselLoop();
            });

            $dots.on('click', function () {
                const nextIndex = Number(jQuery(this).attr('data-slide-index'));

                if (!Number.isNaN(nextIndex)) {
                    updateFeaturedCarousel(nextIndex);
                }
            });

            $prevButton.on('click', function () {
                updateFeaturedCarousel(currentIndex - 1);
            });

            $nextButton.on('click', function () {
                updateFeaturedCarousel(currentIndex + 1);
            });

            $carousel.on('keydown', function (event) {
                if ('ArrowLeft' !== event.key && 'ArrowRight' !== event.key) {
                    return;
                }

                event.preventDefault();
                updateFeaturedCarousel('ArrowLeft' === event.key ? currentIndex - 1 : currentIndex + 1);
            });

            jQuery(window).on('resize', function () {
                $renderSlides = $track.children('[data-carousel-slide]');
                currentRenderIndex = canLoop ? getMiddleRenderIndex(currentIndex) : currentIndex;
                setFeaturedCarouselAnimationEnabled(false);
                syncFeaturedCarouselState(currentRenderIndex, currentIndex);
                positionFeaturedCarousel(currentRenderIndex);
            });

            setFeaturedCarouselAnimationEnabled(false);
            syncFeaturedCarouselState(currentRenderIndex, currentIndex);
            positionFeaturedCarousel(currentRenderIndex);
        });
    }

    function initLoadMoreSections() {
        jQuery('[data-load-more-list]').each(function () {
            const $list = jQuery(this);
            const $items = $list.find('[data-load-more-item]');
            const $button = $list.find('[data-load-more-button]');
            const batchSize = Number($list.attr('data-load-more-initial')) || 5;
            let visibleCount = batchSize;

            function syncLoadMoreVisibility() {
                $items.each(function (index) {
                    jQuery(this).prop('hidden', index >= visibleCount);
                });

                if (visibleCount >= $items.length) {
                    $button.prop('hidden', true).attr('aria-expanded', 'true');
                    return;
                }

                $button.prop('hidden', false).attr('aria-expanded', 'false');
            }

            if (!$button.length || $items.length <= batchSize) {
                $button.prop('hidden', true);
                return;
            }

            syncLoadMoreVisibility();

            $button.on('click', function () {
                visibleCount = Math.min(visibleCount + batchSize, $items.length);
                syncLoadMoreVisibility();
            });
        });
    }

    function initLikeButtons() {
        const likeConfig = window.gakusonLikeConfig || null;

        if (!likeConfig || !likeConfig.ajaxUrl || !likeConfig.action || !likeConfig.nonce) {
            return;
        }

        function formatCount(value) {
            const numericValue = Number(value);

            if (Number.isNaN(numericValue)) {
                return '0';
            }

            return numericValue.toLocaleString('ja-JP');
        }

        function formatLikeCount(value) {
            return '+' + formatCount(value);
        }

        function formatTotalLikeCount(value) {
            return formatCount(value);
        }

        function getDefaultButtonHtml($button) {
            const storedHtml = $button.data('defaultHtml');

            if (typeof storedHtml === 'string' && storedHtml.length) {
                return storedHtml;
            }

            return likeConfig.defaultButtonLabel || 'いいね！';
        }

        function getRemainingMessage(remainingLikes) {
            const numericValue = Number(remainingLikes);

            if (Number.isNaN(numericValue) || numericValue <= 0) {
                return 'この端末では上限に達しました';
            }

            return 'この端末ではあと' + numericValue + '回いいねできます';
        }

        function syncLikeUi(postId, payload) {
            const postIdString = String(postId);
            const likeCount = payload && typeof payload.likeCount !== 'undefined' ? payload.likeCount : 0;
            const remainingLikes = payload && typeof payload.remainingLikes !== 'undefined' ? payload.remainingLikes : 0;
            const reachedLimit = !!(payload && payload.reachedLimit);
            const message = payload && payload.message ? payload.message : '';
            const remainingMessage = getRemainingMessage(remainingLikes);

            jQuery('[data-post-like-count][data-post-id="' + postIdString + '"]').text(formatLikeCount(likeCount));
            jQuery('[data-post-like-total][data-post-id="' + postIdString + '"]').text(formatTotalLikeCount(likeCount));

            jQuery('[data-like-block][data-post-id="' + postIdString + '"]').each(function () {
                const $block = jQuery(this);
                const $button = $block.find('[data-like-button]').first();
                const $remaining = $block.find('[data-like-remaining]').first();
                const $status = $block.find('[data-like-status]').first();

                if ($remaining.length) {
                    $remaining.text(remainingMessage);
                }

                if ($status.length) {
                    $status.text(message === remainingMessage ? '' : message);
                }

                if ($button.length) {
                    $button
                        .prop('disabled', reachedLimit)
                        .attr('aria-disabled', reachedLimit ? 'true' : 'false')
                        .toggleClass('is-disabled', reachedLimit)
                        .html(getDefaultButtonHtml($button));
                }
            });
        }

        jQuery('[data-like-button]').each(function () {
            const $button = jQuery(this);

            if (!$button.data('defaultHtml')) {
                $button.data('defaultHtml', $button.html());
            }
        });

        jQuery(document).on('click', '[data-like-button]', function () {
            const $button = jQuery(this);
            const $block = $button.closest('[data-like-block]');
            const $status = $block.find('[data-like-status]').first();
            const postId = Number($button.attr('data-post-id') || $block.attr('data-post-id'));

            if (!$block.length || !postId || $button.data('isSending')) {
                return;
            }

            if (!$button.data('defaultHtml')) {
                $button.data('defaultHtml', $button.html());
            }

            $button
                .data('isSending', true)
                .prop('disabled', true)
                .attr('aria-disabled', 'true')
                .html('<span class="postReaction_buttonLoading">' + (likeConfig.workingLabel || '送信中...') + '</span>');

            if ($status.length) {
                $status.text('');
            }

            jQuery.ajax({
                url: likeConfig.ajaxUrl,
                method: 'POST',
                dataType: 'json',
                data: {
                    action: likeConfig.action,
                    nonce: likeConfig.nonce,
                    post_id: postId
                }
            }).done(function (response) {
                if (!response || !response.success || !response.data) {
                    const errorMessage = response && response.data && response.data.message ? response.data.message : likeConfig.requestErrorMessage;

                    if ($status.length) {
                        $status.text(errorMessage);
                    }

                    return;
                }

                syncLikeUi(postId, response.data);
            }).fail(function (jqXHR) {
                let errorMessage = likeConfig.requestErrorMessage;

                if (jqXHR && jqXHR.responseJSON && jqXHR.responseJSON.data && jqXHR.responseJSON.data.message) {
                    errorMessage = jqXHR.responseJSON.data.message;
                }

                if ($status.length) {
                    $status.text(errorMessage);
                }
            }).always(function () {
                const $currentBlock = jQuery('[data-like-block][data-post-id="' + String(postId) + '"]').first();
                const $currentButton = $currentBlock.find('[data-like-button]').first();
                const hasReachedLimit = $currentButton.hasClass('is-disabled');

                if ($currentButton.length && !hasReachedLimit) {
                    $currentButton
                        .prop('disabled', false)
                        .attr('aria-disabled', 'false')
                        .html(getDefaultButtonHtml($currentButton));
                }

                $button.data('isSending', false);
            });
        });
    }

    syncBackgroundHeight();
    syncHeaderSpacing();
    initFeaturedCarousels();
    initLoadMoreSections();
    initLikeButtons();

    $searchToggleButtons.on('click', function () {
        if ($searchPanel.hasClass('slideInput__is-open')) {
            closeSearchPanel(true);
            return;
        }

        openSearchPanel(this);
    });

    $searchCloseButtons.on('click', function () {
        closeSearchPanel(true);
    });

    jQuery(document).on('keydown', function (event) {
        if ('Escape' !== event.key) {
            return;
        }

        if ($searchPanel.hasClass('slideInput__is-open')) {
            closeSearchPanel(true);
        }
    });

    jQuery(document).on('click', function (event) {
        const $target = jQuery(event.target);

        if ($searchPanel.hasClass('slideInput__is-open') && !$target.closest('#header-search-panel, .js-header-search-toggle').length) {
            closeSearchPanel(false);
        }
    });

    jQuery(window).on('resize', function () {
        syncBackgroundHeight();
        syncHeaderSpacing();
    });
});

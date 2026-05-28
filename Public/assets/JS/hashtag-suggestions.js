(function () {
    const DEFAULT_ENDPOINT = "App/Controllers/SearchController.php?action=suggestHashtags";

    function appBaseUrl() {
        if (window.APP_BASE_URL) {
            return String(window.APP_BASE_URL).replace(/\/?$/, "/");
        }

        return `${window.location.origin}/`;
    }

    function appUrl(path = "") {
        return appBaseUrl() + String(path).replace(/^\/+/, "");
    }

    function escapeHTML(value) {
        const div = document.createElement("div");
        div.textContent = String(value || "");
        return div.innerHTML;
    }

    function escapeEditorText(value) {
        return escapeHTML(value).replace(/\n/g, "<br>");
    }

    function isContentEditor(control) {
        return control && control.isContentEditable;
    }

    function getEditorPlainText(editor) {
        let text = "";

        function walk(node) {
            if (node.nodeType === Node.TEXT_NODE) {
                text += node.nodeValue || "";
                return;
            }

            if (node.nodeName === "BR") {
                text += "\n";
                return;
            }

            Array.from(node.childNodes).forEach(walk);
        }

        walk(editor);
        return text.replace(/\u00a0/g, " ");
    }

    function getControlValue(control) {
        return isContentEditor(control) ? getEditorPlainText(control) : control.value;
    }

    function getEditorCaretOffset(editor) {
        const selection = window.getSelection();

        if (!selection || selection.rangeCount === 0 || !editor.contains(selection.anchorNode)) {
            return getEditorPlainText(editor).length;
        }

        const range = selection.getRangeAt(0).cloneRange();
        range.selectNodeContents(editor);
        range.setEnd(selection.anchorNode, selection.anchorOffset);

        const fragment = range.cloneContents();
        const holder = document.createElement("div");
        holder.appendChild(fragment);
        return getEditorPlainText(holder).length;
    }

    function setEditorCaretOffset(editor, targetOffset) {
        const selection = window.getSelection();
        const range = document.createRange();
        let current = 0;
        let placed = false;

        function place(node) {
            if (placed) {
                return;
            }

            if (node.nodeType === Node.TEXT_NODE) {
                const length = (node.nodeValue || "").length;
                if (current + length >= targetOffset) {
                    range.setStart(node, Math.max(0, targetOffset - current));
                    placed = true;
                    return;
                }
                current += length;
                return;
            }

            if (node.nodeName === "BR") {
                if (current + 1 >= targetOffset) {
                    range.setStartAfter(node);
                    placed = true;
                    return;
                }
                current += 1;
                return;
            }

            Array.from(node.childNodes).forEach(place);
        }

        place(editor);

        if (!placed) {
            range.selectNodeContents(editor);
            range.collapse(false);
        }

        selection.removeAllRanges();
        range.collapse(true);
        selection.addRange(range);
    }

    function getControlCaretOffset(control) {
        return isContentEditor(control) ? getEditorCaretOffset(control) : (control.selectionStart || 0);
    }

    function renderEditorContent(editor, caretOffset = null) {
        const text = getEditorPlainText(editor);
        let html = "";
        let lastIndex = 0;
        const regex = /(^|[\s])(#([\p{L}\p{N}_]+))/gu;

        text.replace(regex, function (match, prefix, hashtag, name, offset) {
            const hashtagStart = offset + prefix.length;
            html += escapeEditorText(text.slice(lastIndex, hashtagStart));
            html += `<span class="archive-hashtag hashtag-token">${escapeHTML(hashtag)}</span>`;
            lastIndex = hashtagStart + hashtag.length;
            return match;
        });

        html += escapeEditorText(text.slice(lastIndex));
        editor.innerHTML = html || "";

        if (caretOffset !== null) {
            setEditorCaretOffset(editor, caretOffset);
        }
    }

    function syncEditorTarget(editor) {
        const targetId = editor.dataset.contentTarget;
        const target = targetId ? document.getElementById(targetId) : null;

        if (target) {
            target.value = getEditorPlainText(editor);
        }
    }

    function setControlValue(control, value, caretOffset = null) {
        if (!isContentEditor(control)) {
            control.value = value;
            if (caretOffset !== null) {
                control.selectionStart = control.selectionEnd = caretOffset;
            }
            return;
        }

        control.textContent = value;
        renderEditorContent(control, caretOffset);
        syncEditorTarget(control);
    }

    function getActiveHashtagToken(control) {
        const value = getControlValue(control);
        const caret = getControlCaretOffset(control);
        const beforeCaret = value.slice(0, caret);
        const hashIndex = beforeCaret.lastIndexOf("#");

        if (hashIndex < 0) {
            return null;
        }

        const prefix = hashIndex === 0 ? "" : beforeCaret.charAt(hashIndex - 1);

        if (prefix && !/\s/.test(prefix)) {
            return null;
        }

        let tokenEnd = hashIndex + 1;

        while (tokenEnd < value.length && /^[\p{L}\p{N}_]$/u.test(value.charAt(tokenEnd))) {
            tokenEnd++;
        }

        if (caret > tokenEnd) {
            return null;
        }

        const keyword = value.slice(hashIndex + 1, tokenEnd);

        if (keyword.length < 1 || !/^[\p{L}\p{N}_]+$/u.test(keyword)) {
            return null;
        }

        return {
            start: hashIndex,
            end: tokenEnd,
            keyword: keyword
        };
    }

    function normalizeSuggestions(payload) {
        const items = payload && Array.isArray(payload.hashtags)
            ? payload.hashtags
            : (Array.isArray(payload) ? payload : []);

        return items.map(function (item) {
            return {
                id: Number(item.HashtagID || item.hashtag_id || 0),
                name: String(item.HashtagName || item.name || "").replace(/^#/, ""),
                usageCount: Number(item.UsageCount || item.usage_count || 0)
            };
        }).filter(function (item) {
            return item.name !== "";
        });
    }

    function createBox() {
        const box = document.createElement("div");
        box.className = "hashtag-suggestion-box";
        box.hidden = true;
        document.body.appendChild(box);
        return box;
    }

    function getTextareaCaretPosition(textarea, index) {
        const rect = textarea.getBoundingClientRect();
        const styles = window.getComputedStyle(textarea);
        const mirror = document.createElement("div");
        const marker = document.createElement("span");
        const properties = [
            "boxSizing",
            "width",
            "fontFamily",
            "fontSize",
            "fontWeight",
            "fontStyle",
            "letterSpacing",
            "textTransform",
            "lineHeight",
            "paddingTop",
            "paddingRight",
            "paddingBottom",
            "paddingLeft",
            "borderTopWidth",
            "borderRightWidth",
            "borderBottomWidth",
            "borderLeftWidth",
            "whiteSpace",
            "wordBreak",
            "overflowWrap"
        ];

        mirror.style.position = "absolute";
        mirror.style.visibility = "hidden";
        mirror.style.left = "-9999px";
        mirror.style.top = "0";
        mirror.style.minHeight = "0";
        mirror.style.whiteSpace = "pre-wrap";
        mirror.style.overflowWrap = "break-word";

        properties.forEach(function (property) {
            mirror.style[property] = styles[property];
        });

        mirror.textContent = textarea.value.slice(0, index);
        marker.textContent = "\u200b";
        mirror.appendChild(marker);
        document.body.appendChild(mirror);

        const markerRect = marker.getBoundingClientRect();
        const lineHeight = parseFloat(styles.lineHeight) || parseFloat(styles.fontSize) * 1.4 || 20;
        const left = rect.left + window.scrollX + marker.offsetLeft - textarea.scrollLeft;
        const top = rect.top + window.scrollY + marker.offsetTop - textarea.scrollTop + lineHeight + 4;

        document.body.removeChild(mirror);

        return {
            left: Math.max(rect.left + window.scrollX, Math.min(left, rect.right + window.scrollX - 220)),
            top: Math.max(rect.top + window.scrollY, Math.min(top, rect.bottom + window.scrollY + 6))
        };
    }

    function getEditorCaretPosition(editor) {
        const rect = editor.getBoundingClientRect();
        const selection = window.getSelection();

        if (selection && selection.rangeCount > 0 && editor.contains(selection.anchorNode)) {
            const range = selection.getRangeAt(0).cloneRange();
            range.collapse(false);
            const rangeRect = range.getBoundingClientRect();

            if (rangeRect && (rangeRect.left || rangeRect.top)) {
                return {
                    left: rangeRect.left + window.scrollX,
                    top: rangeRect.bottom + window.scrollY + 6
                };
            }
        }

        return { left: rect.left + window.scrollX, top: rect.bottom + window.scrollY + 6 };
    }

    function positionBox(control, box) {
        const rect = control.getBoundingClientRect();
        const token = getActiveHashtagToken(control);
        const caret = token
            ? (isContentEditor(control) ? getEditorCaretPosition(control) : getTextareaCaretPosition(control, token.end))
            : { left: rect.left + window.scrollX, top: rect.bottom + window.scrollY + 6 };
        const width = Math.min(Math.max(rect.width * 0.58, 240), 340);

        box.style.left = `${caret.left}px`;
        box.style.top = `${caret.top}px`;
        box.style.width = `${Math.min(width, rect.width)}px`;
    }

    function bindControl(control) {
        if (!control || control.dataset.hashtagSuggestionsBound === "1") {
            return;
        }

        if (!isContentEditor(control) && control.hidden) {
            return;
        }

        control.dataset.hashtagSuggestionsBound = "1";

        const endpoint = control.dataset.hashtagSuggestEndpoint || appUrl(DEFAULT_ENDPOINT);
        const box = createBox();
        let debounceTimer = null;
        let activeIndex = -1;
        let suggestions = [];
        let activeToken = null;
        let lastKeyword = "";
        let isComposing = false;

        if (isContentEditor(control)) {
            renderEditorContent(control, getControlCaretOffset(control));
            syncEditorTarget(control);
        }

        function hide() {
            control.classList.remove("hashtag-composing");
            box.hidden = true;
            box.innerHTML = "";
            suggestions = [];
            activeIndex = -1;
        }

        function render(keyword, items) {
            const keywordLower = keyword.toLowerCase();
            const hasExact = items.some(function (item) {
                return item.name.toLowerCase() === keywordLower;
            });

            suggestions = items.slice(0, hasExact ? 8 : 7);

            if (!hasExact && keyword !== "") {
                suggestions.push({
                    id: 0,
                    name: keyword,
                    usageCount: 0,
                    isNew: true
                });
            }

            if (suggestions.length === 0) {
                hide();
                return;
            }

            activeIndex = 0;
            box.innerHTML = "";

            suggestions.forEach(function (item, index) {
                const button = document.createElement("button");
                button.type = "button";
                button.className = "hashtag-suggestion-item" + (index === activeIndex ? " active" : "") + (item.isNew ? " is-new" : "");
                button.innerHTML = item.isNew
                    ? `
                        <span class="hashtag-suggestion-name">#${escapeHTML(item.name)}</span>
                        <span class="hashtag-suggestion-meta">Tạo hashtag mới</span>
                    `
                    : `
                        <span class="hashtag-suggestion-name">#${escapeHTML(item.name)}</span>
                        <span class="hashtag-suggestion-meta">${item.usageCount} bài viết</span>
                    `;

                button.addEventListener("mousedown", function (event) {
                    event.preventDefault();
                    insert(item.name);
                });

                box.appendChild(button);
            });

            positionBox(control, box);
            box.hidden = false;
        }

        function move(direction) {
            const items = box.querySelectorAll(".hashtag-suggestion-item");

            if (items.length === 0) {
                return;
            }

            activeIndex = (activeIndex + direction + items.length) % items.length;

            items.forEach(function (item, index) {
                item.classList.toggle("active", index === activeIndex);
            });
        }

        function insert(name) {
            activeToken = getActiveHashtagToken(control);

            if (!activeToken) {
                hide();
                return;
            }

            const value = getControlValue(control);
            const before = value.slice(0, activeToken.start);
            const after = value.slice(activeToken.end);
            const suffix = after !== "" && /^\s/.test(after) ? "" : " ";
            const inserted = "#" + name + suffix;
            const caretOffset = before.length + inserted.length;

            setControlValue(control, before + inserted + after, caretOffset);
            control.focus();
            hide();
        }

        function fetchSuggestions(keyword) {
            lastKeyword = keyword;
            const separator = endpoint.includes("?") ? "&" : "?";

            fetch(endpoint + separator + "q=" + encodeURIComponent(keyword), {
                headers: { "Accept": "application/json" }
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (payload) {
                    if (keyword !== lastKeyword) {
                        return;
                    }

                    render(keyword, normalizeSuggestions(payload));
                })
                .catch(hide);
        }

        control.addEventListener("compositionstart", function () {
            isComposing = true;
        });

        control.addEventListener("compositionend", function () {
            isComposing = false;
            if (isContentEditor(control)) {
                const caret = getControlCaretOffset(control);
                renderEditorContent(control, caret);
                syncEditorTarget(control);
            }
        });

        control.addEventListener("input", function () {
            window.clearTimeout(debounceTimer);

            if (isContentEditor(control)) {
                const caret = getControlCaretOffset(control);
                if (!isComposing) {
                    renderEditorContent(control, caret);
                }
                syncEditorTarget(control);
            }

            if (isComposing) {
                return;
            }

            activeToken = getActiveHashtagToken(control);

            if (!activeToken) {
                hide();
                return;
            }

            control.classList.add("hashtag-composing");

            debounceTimer = window.setTimeout(function () {
                fetchSuggestions(activeToken.keyword);
            }, 180);
        });

        control.addEventListener("keyup", function (event) {
            if (["ArrowDown", "ArrowUp", "Enter", "Escape"].includes(event.key)) {
                return;
            }

            if (!box.hidden) {
                activeToken = getActiveHashtagToken(control);
                if (!activeToken) {
                    hide();
                } else {
                    positionBox(control, box);
                }
            }
        });

        control.addEventListener("keydown", function (event) {
            if (box.hidden) {
                return;
            }

            if (event.key === "ArrowDown") {
                event.preventDefault();
                move(1);
                return;
            }

            if (event.key === "ArrowUp") {
                event.preventDefault();
                move(-1);
                return;
            }

            if (event.key === "Enter") {
                event.preventDefault();
                if (activeIndex >= 0 && suggestions[activeIndex]) {
                    insert(suggestions[activeIndex].name);
                }
                return;
            }

            if (event.key === "Escape") {
                event.preventDefault();
                hide();
            }
        });

        control.addEventListener("blur", function () {
            if (isContentEditor(control)) {
                syncEditorTarget(control);
            }
            window.setTimeout(hide, 120);
        });

        window.addEventListener("resize", function () {
            if (!box.hidden) {
                positionBox(control, box);
            }
        });

        window.addEventListener("scroll", function () {
            if (!box.hidden) {
                positionBox(control, box);
            }
        }, true);

        document.addEventListener("mousedown", function (event) {
            if (!box.contains(event.target) && event.target !== control) {
                hide();
            }
        });
    }

    window.initArchiveHashtagSuggestions = function (root) {
        const scope = root || document;
        scope.querySelectorAll("textarea[name='content'], textarea[data-hashtag-suggestions='true'], .post-content-editor[contenteditable='true']").forEach(bindControl);
    };

    window.syncArchiveContentEditors = function (root) {
        const scope = root || document;
        scope.querySelectorAll(".post-content-editor[contenteditable='true']").forEach(syncEditorTarget);
    };

    document.addEventListener("DOMContentLoaded", function () {
        window.initArchiveHashtagSuggestions(document);
    });
})();

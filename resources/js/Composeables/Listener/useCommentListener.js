export function useCommentListener(commentList, projectId) {
    function init() {
        Echo.private('project.' + projectId)
            .listen('.comment.add', (data) => {
                const newComment = data.comment;

                // Wenn commentList ein Ref ist, immer .value verwenden
                const list = Array.isArray(commentList.value)
                    ? commentList.value
                    : commentList;

                const existingCommentIndex = list.findIndex(
                    (comment) => comment.id === newComment.id
                );

                if (existingCommentIndex !== -1) {
                    list.splice(existingCommentIndex, 1);
                }

                // Neuen Kommentar vorne einfügen
                list.unshift(newComment);
            })
            .listen('.comment.delete', (data) => {
                const deletedComment = data.comment;

                const list = Array.isArray(commentList.value)
                    ? commentList.value
                    : commentList;

                const deletedCommentIndex = list.findIndex(
                    (comment) => comment.id === deletedComment.id
                );

                if (deletedCommentIndex !== -1) {
                    list.splice(deletedCommentIndex, 1);
                }
            });
    }

    return { init };
}

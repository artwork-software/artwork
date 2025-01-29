export function useCommentListener(commentList, projectId) {
    function init() {
        Echo.private('project.' + projectId)
            .listen('.comment.add', (data) => {
                const newComment = data.comment;

                // Überprüfen, ob der Kommentar bereits existiert
                const existingCommentIndex = commentList.findIndex(
                    (comment) => comment.id === newComment.id
                );

                if (existingCommentIndex !== -1) {
                    // Entfernen des bestehenden Kommentars aus der Liste
                    commentList.splice(existingCommentIndex, 1);
                }
                // Neuen Kommentar an den Anfang der Liste einfügen
                commentList.unshift(newComment);
            })
            .listen('.comment.delete', (data) => {
                const deletedComment = data.comment;

                // Index des zu löschenden Kommentars finden
                const deletedCommentIndex = commentList.findIndex(
                    (comment) => comment.id === deletedComment.id
                );

                if (deletedCommentIndex !== -1) {
                    // Kommentar aus der Liste entfernen
                    commentList.splice(deletedCommentIndex, 1);
                }
            });
    }

    return { init };
}
<li data-id="$File.ID" data-file-link="$File.Link"
    class="<% if $Scope.CanDelete && $Scope.CanDetach %>dual-perm<% end_if %>
            $Scope.CSSSize
           <% if $File.Orientation > -1 %>dropzone-image<% else %>dropzone-file<% end_if %>"
    style="height:{$Scope.SelectedThumbnailHeight}px;<% if $Scope.View == 'grid' %>width:{$Scope.SelectedThumbnailWidth}px;<% end_if %>"
>

    <span class="file-icon" style="width:{$Scope.SelectedThumbnailWidth}px;height:{$Scope.SelectedThumbnailHeight}px;">
            <img
                <% if $Scope.SelectedThumbnailWidth > $Scope.SelectedThumbnailHeight %>
                    style="height:{$Scope.SelectedThumbnailHeight}px"
                <% else %>
                    style="width:{$Scope.SelectedThumbnailWidth}px"
                <% end_if %>
                <% if $File.IsImage && $File.Orientation > -1 %>
                    src="$File.Fill($Scope.SelectedThumbnailWidth, $Scope.SelectedThumbnailHeight).URL"
                <% else %>
                    src="$Scope.ThumbnailsDir/{$File.Extension.LowerCase}.png" onerror="this.src='$Scope.ThumbnailsDir/_blank.png'" onload="this.parentNode.style.backgroundImage='url('+this.src+')';this.style.display='none';"
                <% end_if %>
            >
    </span>
    <span class="file-meta file-name truncate" data-dz-name>$File.Title</span>
    <span class="file-meta file-size">
            <%t Dropzone.ADDEDON 'Added on {date}' date=$File.Created.Format('j M Y') %>
        · <span data-dz-size>$File.Size</span>
    </span>
    <span class="dropzone-actions">
        <% if $Scope.CanDetach %>
            <span data-detach class="dropzone-action detach">
                <span><%t Dropzone.DETACHFILE 'remove' %></span>
                <img src="$resourceURL('unclecheese/dropzone:images/remove.png')" width="16">
            </span>
        <% end_if %>
        <% if $Scope.CanDelete %>
            <span data-delete class="dropzone-action delete">
                <span><%t Dropzone.MARKFORDELETION 'delete' %></span>
                <img src="$resourceURL('unclecheese/dropzone:images/trash.png')" width="16">
            </span>
        <% end_if %>
    </span>

    <% if $Scope.CanDetach %>
        <span class="overlay detach-overlay">
            <span>
                <h5><%t Dropzone.REMOVED 'removed' %></h5>
                <small><%t Dropzone.CHANGEAFTERSAVE 'The change will take effect after you save.' %></small>
                <span data-delete-revert class="revert"><img src="$resourceURL('unclecheese/dropzone:images/undo.png')" width="16"></span>
            </span>
        </span>
    <% end_if %>
    <% if $Scope.CanDelete %>
        <span class="overlay delete-overlay">
            <span>
                <h5><%t Dropzone.DELETED 'deleted' %></h5>
                <small><%t Dropzone.CHANGEAFTERSAVE 'The change will take effect after you save.' %></small>
                <span data-delete-revert class="revert"><img src="$resourceURL('unclecheese/dropzone:images/undo.png')" width="16"></span>
            </span>
        </span>
    <% end_if %>

</li>

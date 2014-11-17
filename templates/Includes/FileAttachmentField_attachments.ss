            <li data-id="$File.ID" 
                style="height:{$Scope.SelectedThumbnailHeight}px;<% if $Scope.View == 'grid' %>width:{$Scope.SelectedThumbnailWidth}px;<% end_if %>"
            >
                <% if $Scope.CanDetach || $Scope.CanDelete %>
                    <span data-delete-control class="remove"></span>
                    <div class="delete-options">
                        <div class="delete-buttons">
                            <% if $Scope.CanDetach %>
                            <a class="detach" data-detach><%t Dropzone.DETACHFILE "Detach file" %></a>
                            <% end_if %>
                            <% if $Scope.CanDelete && $CanDelete %>
                            <a class="delete" data-delete><%t Dropzone.MARKFORDELETION "Mark for deletion" %></a>
                            <% end_if %>
                        </div>          
                        <span data-delete-control class="remove"></span>
                    </div>
                <% end_if %>
                <span class="file-icon" <% if $Scope.View == 'list' %>style="width:{$Scope.SelectedThumbnailWidth}px;"<% end_if %>>                                    
                        <img 
                            <% if $Scope.SelectedThumbnailWidth > $Scope.SelectedThumbnailHeight %>
                                style="height:{$Scope.SelectedThumbnailHeight}px"
                            <% else %>
                                style="width:{$Scope.SelectedThumbnailWidth}px"
                            <% end_if %>
                            <% if $File.Orientation > -1 %>
                                src="$File.CroppedImage($Scope.SelectedThumbnailWidth, $Scope.SelectedThumbnailHeight).URL"
                            <% else %>
                                src="$Scope.ThumbnailsDir/{$File.Extension.LowerCase}.png" onerror="this.src='$Scope.ThumbnailsDir/_blank.png'"
                            <% end_if %>
                        >
                </span>
                <span class="file-name truncate" data-dz-name>$File.Title</span>
                <span class="file-size" data-dz-size>$File.Size</span>
            </li>            

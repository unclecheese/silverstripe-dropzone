            <li data-id="$File.ID" 
                style="height:{$Scope.SelectedThumbnailHeight}px;<% if $Scope.View == 'grid' %>width:{$Scope.SelectedThumbnailWidth}px;<% end_if %>"
            >
                <% if $Scope.CanDetach && $Scope.CanDelete %>
                    <span data-detach class="action detach"><%t Dropzone.DETACHFILE 'remove' %> <img src="$Scope.DropzoneDir/images/remove.png" width="12"></span>
                    <span data-delete class="action delete"><%t Dropzone.MARKFORDELEION 'delete' %> <img src="$Scope.DropzoneDir/images/trash.png" width="12"></span>
                        <span class="overlay detach-overlay">
                            <span>
                                <small><%t Dropzone.FILEREMOVED 'This file will be removed when you save.' %></small>
                                <span data-delete-revert class="revert"><img src="$Scope.DropzoneDir/images/undo.png" width="12"> Undo</span>
                            </span>
                        </span>
                        <span class="overlay delete-overlay">
                            <span>
                                <small><%t Dropzone.FILEDELETED 'This file will be deleted when you save.' %></small>
                                <span data-delete-revert class="revert"><img src="$Scope.DropzoneDir/images/undo.png" width="12"> Undo</span>
                            </span>
                        </span>                    
                <% else %>
                    <% if $Scope.CanDetach %>
                        <span data-detach class="action detach"><img src="$Scope.DropzoneDir/images/remove.png" width="16"></span>
                        <span class="overlay detach-overlay">
                            <span>
                                <small><%t Dropzone.FILEREMOVED 'This file will be removed when you save.' %></small>
                                <span data-delete-revert class="revert"><img src="$Scope.DropzoneDir/images/undo.png" width="16"> Undo</span>
                            </span>
                        </span>
                    <% end_if %>
                    <% if $Scope.CanDelete %>
                        <span data-delete class="action delete"><img src="$Scope.DropzoneDir/images/trash.png" width="16"></span>
                        <span class="overlay delete-overlay">
                            <span>
                                <small><%t Dropzone.FILEDELETED 'This file will be deleted when you save.' %></small>
                                <span data-delete-revert class="revert"><img src="$Scope.DropzoneDir/images/undo.png" width="16"> Undo</span>
                            </span>
                        </span>                    
                    <% end_if %>
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
                <span class="file-meta file-name truncate" data-dz-name>$File.Title</span>
                <span class="file-meta file-size" data-dz-size>$File.Size</span>                    
            </li>            

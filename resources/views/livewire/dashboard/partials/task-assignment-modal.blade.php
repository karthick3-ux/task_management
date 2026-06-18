<!-- Task Assignment Management Modal - Updated with Sequential Logic -->
@if($showAssignmentManagementModal && $managingTask)
<div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold">
                    <i class="icofont-users-alt-4 me-2"></i>Manage Assignments: {{ $managingTask->task_name }}
                </h5>
                <button type="button" class="btn-close btn-close-white" wire:click="closeAssignmentModal"></button>
            </div>
            <div class="modal-body">
                 @if (session()->has('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="icofont-check-circled me-2"></i>
        <strong>Success!</strong> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if (session()->has('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="icofont-close-circled me-2"></i>
        <strong>Error!</strong> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

                <!-- Task Status Info -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="alert alert-info mb-0">
                            <div class="d-flex align-items-center">
                                <i class="icofont-info-circle fs-4 me-3"></i>
                                <div>
                                    <h6 class="alert-heading mb-1">Task Status: {{ ucwords($managingTask->task_status) }}</h6>
                                    <small class="mb-0">Project: {{ $managingTask->project->project_name }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-end">
                            @can('manage_task_assignments')
                            <button type="button" class="btn btn-primary btn-sm" wire:click="addAssignmentRow" 
                                    @if($managingTask->task_status === 'on hold') disabled @endif>
                                <i class="icofont-plus-circle me-1"></i>Add Assignment
                            </button>
                            @endcan
                        </div>
                    </div>
                </div>

                <!-- Assignments Table -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th width="5%">S.no</th>
                                <th width="15%">User</th>
                                <th width="20%">Work Description</th>
                                <th width="8%">No of Days</th>
                                <th width="10%">Start Date</th>
                                <th width="10%">Expected</th>
                                <th width="10%">Deadline</th>
                                <th width="12%">Status</th>
                                <th width="10%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($assignmentData as $index => $assignment)
                            @php
                                $canEdit = auth()->user()->isSuperAdmin() || 
                                          (isset($assignment['user_id']) && $assignment['user_id'] == auth()->id()) || (auth()->user()->can('manage_task_assignments') && $assignment['user_id'] == auth()->id()) || auth()->user()->can('assign_task_users');
                                $isCompleted = isset($assignment['status']) && $assignment['status'] === 'Completed';
                                $isReassigned = isset($assignment['status']) && $assignment['status'] === 'Reassigned';
                                $isTaskOnHold = $managingTask->task_status === 'on hold';
                                $canStartWork = true;
                                $isOverdue = isset($assignment['deadline']) && 
                                           $assignment['deadline'] < now()->format('Y-m-d') && 
                                           !in_array($assignment['status'] ?? '', ['Completed', 'Reassigned']);
                                $isFirstAssignment = $index === 0;
                                                            $assignment['start_date']= isset($assignment['start_date']) ? $assignment['start_date']:'';


                            @endphp
                            
                            <tr class="{{ $isOverdue ? 'table-danger' : '' }} {{ $isCompleted ? 'table-success' : '' }} {{ $isTaskOnHold ? 'table-secondary' : '' }}">
                                <!-- Sequence Number -->
                                <td class="text-center">
                                    <span class="badge bg-{{ $isCompleted ? 'success' : ($canStartWork ? 'primary' : 'secondary') }} fs-6">
                                        {{ $assignment['sequence_number'] ?? $index + 1 }}
                                    </span>
                                </td>
                                
                                <!-- User Selection -->
                                <td>
                                    @if($canEdit  && !$isTaskOnHold && !$isReassigned && auth()->user()->can('assign_task_users'))
                                          @php $edit_display='d-block';
                                          $value_display='d-none';
                                           @endphp
                                    @else
                                     @php $edit_display='d-none'; 
                                     $value_display='d-block';
                                     @endphp
                                    @endif

                                
                                        <select wire:model="assignmentData.{{ $index }}.user_id" 
                                                class="form-select form-select-sm {{$edit_display}} @error('assignmentData.'.$index.'.user_id') is-invalid @enderror">
                                            <option value="">Select User</option>
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}">
                                                    {{ $user->name }}
                                                    @if($user->department)
                                                        ({{ $user->department->name }})
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>

                               
                              
                              
                                  
                                        @if(isset($assignment['user_id']))
                                            @php $user = $users->find($assignment['user_id']); @endphp
                                            @if($user)
                                                <div class="d-flex align-items-center {{$value_display}}">
                                                    <div class="avatar sm rounded-circle me-2 " style="background-color: {{ $user->color }}; color: white;">
                                                        {{ substr($user->name, 0, 1) }}
                                                    </div>
                                                    <div>
                                                        <div class="fw-medium">{{ $user->name }}</div>
                                                        @if($user->department)
                                                            <small class="text-muted">{{ $user->department->name }}</small>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-muted {{$value_display}}">Not assigned</span>
                                        @endif
                       
                                 
                                    @error('assignmentData.'.$index.'.user_id') 
                                        <div class="invalid-feedback d-block">{{ $message }}</div> 
                                    @enderror
                                </td>
                                
                                <!-- Work Description -->
                                <td>
                                    @if($canEdit  && !$isTaskOnHold && !$isReassigned)
                                    @php $edit_display='d-block';
                                          $value_display='d-none';
                                           @endphp
                                    @else
                                     @php $edit_display='d-none'; 
                                     $value_display='d-block';
                                     @endphp
                                    @endif
                                        <textarea wire:model="assignmentData.{{ $index }}.work_description"
                                                  class="form-control form-control-sm {{$edit_display}} @error('assignmentData.'.$index.'.work_description') is-invalid @enderror"
                                                  rows="2"
                                                  placeholder="Describe the work..."
                                                  ></textarea>
                                
                                        <div class="text-wrap {{$value_display}}" style="max-width: 180px;">
                                            {{ $assignment['work_description'] ?? '' }}
                                        </div>
                                  
                                    @error('assignmentData.'.$index.'.work_description') 
                                        <div class="invalid-feedback {{$value_display}}">{{ $message }}</div> 
                                    @enderror
                                </td>
                                
                                <!-- No of Days -->
                                <td>
                                    @if($canEdit  && !$isTaskOnHold && !$isReassigned)

                                      @php $edit_display='d-block';
                                          $value_display='d-none';
                                           @endphp
                                    @else
                                     @php $edit_display='d-none'; 
                                     $value_display='d-block';
                                     @endphp
                                    @endif
                                        <input type="number" 
                                               wire:model="assignmentData.{{ $index }}.no_of_days"
                                               wire:change="calculateAssignmentDatesFromDays({{ $index }})"
                                               class="form-control form-control-sm {{$edit_display}} @error('assignmentData.'.$index.'.no_of_days') is-invalid @enderror"
                                               min="0"
                                               placeholder="Days"
                                               @if(!$canStartWork && !auth()->user()->isSuperAdmin()) readonly @endif>
                                   
                                        <span class="badge bg-info {{$value_display}}">{{ $assignment['no_of_days'] ?? '-' }}</span>
                                   
                                    @error('assignmentData.'.$index.'.no_of_days') 
                                        <div class="invalid-feedback {{$value_display}}">{{ $message }}</div> 
                                    @enderror
                                </td>
                                
                                <!-- Start Date -->
                                <td>
                                    @if($canEdit && !$isReassigned && !$isTaskOnHold)
                                      @php $edit_display='d-block';
                                          $value_display='d-none';
                                           @endphp
                                    @else
                                     @php $edit_display='d-none'; 
                                     $value_display='d-block';
                                     @endphp
                                    @endif
                                        <input type="date" 
                                               wire:model="assignmentData.{{ $index }}.start_date"
                                              
                                               class="form-control form-control-sm {{$edit_display}} @error('assignmentData.'.$index.'.start_date') is-invalid @enderror"
                                              >
                                  
                                        <small class="{{$value_display}}">{{ isset($assignment['start_date']) ? \Carbon\Carbon::parse($assignment['start_date'])->format('M d, Y') : '-' }}</small>
                                  
                                    @error('assignmentData.'.$index.'.start_date') 
                                        <div class="invalid-feedback {{$value_display}}">{{ $message }}</div> 
                                    @enderror
                                </td>

                                  <!-- Expected Date -->
                                <td>
                                    @if($canEdit  && !$isReassigned && !$isTaskOnHold)
                                      @php $edit_display='d-block';
                                          $value_display='d-none';
                                           @endphp
                                    @else
                                     @php $edit_display='d-none'; 
                                     $value_display='d-block';
                                     @endphp
                                    @endif
                                        <input type="date" 
                                               wire:model="assignmentData.{{ $index }}.expected_date"
                                            
                                               class="form-control form-control-sm {{$edit_display}} @error('assignmentData.'.$index.'.expected_date') is-invalid @enderror">
                                   
                                        <small class="{{$value_display}}">{{ isset($assignment['expected_date']) ? \Carbon\Carbon::parse($assignment['expected_date'])->format('M d, Y') : '-' }}</small>
                                    
                                    @error('assignmentData.'.$index.'.expected_date') 
                                        <div class="invalid-feedback {{$value_display}}">{{ $message }}</div> 
                                    @enderror
                                </td>
                                
                                <!-- Deadline -->
                                <td>
                                    @if($canEdit && !$isReassigned && !$isTaskOnHold )
                                      @php $edit_display='d-block';
                                          $value_display='d-none';
                                           @endphp
                                    @else
                                     @php $edit_display='d-none'; 
                                     $value_display='d-block';
                                     @endphp
                                    @endif
                                        <input type="date" 
                                               wire:model="assignmentData.{{ $index }}.deadline"
                                            
                                               class="form-control form-control-sm {{$edit_display}} @error('assignmentData.'.$index.'.deadline') is-invalid @enderror">
                                
                                        <small class="{{ $isOverdue ? 'text-danger fw-bold' : '' }} {{$value_display}}">
                                            {{ isset($assignment['deadline']) ? \Carbon\Carbon::parse($assignment['deadline'])->format('M d, Y') : '-' }}
                                            @if($isOverdue)
                                                <br><span class="badge bg-danger {{$value_display}}">Overdue</span>
                                            @endif
                                        </small>
                                  
                                    @error('assignmentData.'.$index.'.deadline') 
                                        <div class="invalid-feedback {{$value_display}}">{{ $message }}</div> 
                                    @enderror
                                </td>
                                
                                <!-- Status -->
                                <td>
                                    @if($canEdit && !$isReassigned && !$isTaskOnHold && ($canStartWork || auth()->user()->isSuperAdmin()))
                                      @php 
                                      $edit_display='d-block';
                                          $value_display='d-none';
                                           @endphp
                                    @else
                                     @php $edit_display='d-none'; 
                                     $value_display='d-block';
                                     @endphp
                                    @endif
                                        <select wire:model="assignmentData.{{ $index }}.status"
                                                wire:change="handleStatusChange({{ $index }}, $event.target.value)"
                                                class="form-select form-select-sm {{$edit_display}} @error('assignmentData.'.$index.'.status') is-invalid @enderror">
                                            <option value="Pending">Pending</option>
                                            <option value="Inprogress">In Progress</option>
                                            <option value="Completed">Completed</option>
                                            <option value="Not Completed">Not Completed</option>
                                            <option value="Reassigned">Reassigned</option>
                                        </select>
                                   
                                        <span class="badge {{ 
                                            ($assignment['status'] ?? '') === 'Completed' ? 'bg-success' : (
                                            ($assignment['status'] ?? '') === 'Inprogress' ? 'bg-info' : (
                                            ($assignment['status'] ?? '') === 'Reassigned' ? 'bg-secondary' : (
                                            ($assignment['status'] ?? '') === 'Not Completed' ? 'bg-danger' : 'bg-warning'
                                        ))) }} {{$value_display}}">
                                            {{ $assignment['status'] ?? 'Pending' }}
                                        </span>
                                  
                                    @error('assignmentData.'.$index.'.status') 
                                        <div class="invalid-feedback {{$value_display}}">{{ $message }}</div> 
                                    @enderror
                                </td>
                                
                                <!-- Actions -->
                                <td>
                                    @can('manage_task_assignments')
                                        @if(!$isTaskOnHold)
                                            <div class="btn-group-vertical" role="group">
                                                <!-- Remove Assignment -->
                                                @if(count($assignmentData) > 1 )
                                                <button type="button" 
                                                        class="btn btn-outline-danger btn-sm mb-1" 
                                                        wire:click="removeAssignmentRow({{ $index }})"
                                                        onclick="return confirm('Remove this assignment?')"
                                                        title="Remove assignment">
                                                    <i class="icofont-ui-delete"></i>
                                                </button>
                                                @endif
                                                
                                                <!-- Move Up -->
                                                @if($index > 0)
                                                <button type="button" 
                                                        class="btn btn-outline-secondary btn-sm mb-1" 
                                                        wire:click="moveAssignmentUp2({{ $index }})"
                                                        title="Move up">
                                                    <i class="icofont-arrow-up"></i>
                                                </button>
                                                @endif
                                                
                                                <!-- Move Down -->
                                                @if($index < count($assignmentData) - 1)
                                                <button type="button" 
                                                        class="btn btn-outline-secondary btn-sm" 
                                                        wire:click="moveAssignmentDown2({{ $index }})"
                                                        title="Move down">
                                                    <i class="icofont-arrow-down"></i>
                                                </button>
                                                @endif
                                            </div>
                                        @else
                                            @if($canEdit  && !$isReassigned)
                                                <small class="text-muted">Editable</small>
                                            @elseif($isCompleted)
                                                <small class="text-success">Completed</small>
                                            @elseif($isReassigned)
                                                <small class="text-secondary">Reassigned</small>
                                            @else
                                                <small class="text-muted">-</small>
                                            @endif
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Assignment Summary Info -->
               
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" wire:click="closeAssignmentModal">
                    <i class="icofont-close-line me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-success" wire:click="saveAssignments" wire:loading.attr="disabled">
                    <span wire:loading.remove>
                        <i class="icofont-save me-1"></i>Save Changes
                    </span>
                    <span wire:loading>
                        <i class="icofont-spinner-alt-3 icofont-spin me-1"></i>Saving...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

@endif
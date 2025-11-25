<x-layout-dashboard>

<!-- Contents Starts Here -->

    <main role="main" class="container">

      <div class="starter-template">
      
        <table class="table table-striped table-bordered">

            <tr class="text-center">
                <th>Sr.</th>
                <th>Post Code</th>
                <th>Cadre</th>
                <th>Total Post</th>
                <th>MQ</th>
                <th>CFF</th>
                <th>EM</th>
                <th>PHC</th>
                <th>Allocated Post</th>
            </tr>

            @php

              $total_sum = 0; 
              $total_left_sum = 0; 
              $mq_sum = 0; 
              $mq_left_sum = 0; 
              $cff_sum = 0; 
              $cff_left_sum = 0; 
              $em_sum = 0; 
              $em_left_sum = 0; 
              $phc_sum = 0; 
              $phc_left_sum = 0; 
              $allocated_sum = 0; 

            @endphp

            @foreach( $posts as $post )

            <tr class="text-center">
                <td>{{ $loop->index + 1 }}</td>
                <td>{{ $post->cadre_code }}</td>
                <td>{{ $cadres->where('cadre_code', $post->cadre_code)->first()->cadre_abbr }}</td>
                <td>
                  {{ $post->total_post }}
                  <span class="text-danger"> [ {{ $post->total_post_left }} ]</span>
                  @php $total_sum += $post->total_post @endphp
                  @php $total_left_sum += $post->total_post_left @endphp
                </td>
                <td>
                  {{ $post->mq_post }}
                  <span class="text-danger"> [ {{ $post->mq_post_left }} ]</span>
                  @php $mq_sum += $post->mq_post @endphp
                  @php $mq_left_sum += $post->mq_post_left @endphp
                </td>
                <td>
                  {{ $post->cff_post }}
                  <span class="text-danger"> [ {{ $post->cff_post_left }} ]</span>
                  @php $cff_sum += $post->cff_post @endphp
                  @php $cff_left_sum += $post->cff_post_left @endphp
                </td>
                <td>
                  {{ $post->em_post }}
                  <span class="text-danger"> [ {{ $post->em_post_left }} ]</span>
                  @php $em_sum += $post->em_post @endphp
                  @php $em_left_sum += $post->em_post_left @endphp
                </td>
                <td>
                  {{ $post->phc_post }}
                  <span class="text-danger"> [ {{ $post->phc_post_left }} ]</span>
                  @php $phc_sum += $post->phc_post @endphp
                  @php $phc_left_sum += $post->phc_post_left @endphp
                </td>
                <td>
                  {{ $post->allocated_post_count ?? '0' }}
                  @php $allocated_sum += $post->allocated_post_count @endphp
                </td>
            </tr>

            @endforeach


            <tr class="text-center">
                <th>-</th>
                <th>-</th>
                <th>-</th>
                <th>
                  {{ $total_sum }}
                  <span class="text-danger"> [ {{ $total_left_sum }} ]</span>
                </th>
                <th>
                  {{ $mq_sum }}
                  <span class="text-danger"> [ {{ $mq_left_sum }} ]</span>
                </th>
                <th>
                  {{ $cff_sum }}
                  <span class="text-danger"> [ {{ $cff_left_sum }} ]</span>
                </th>
                <th>
                  {{ $em_sum }}
                  <span class="text-danger"> [ {{ $em_left_sum }} ]</span>
                </th>
                <th>
                  {{ $phc_sum }}
                  <span class="text-danger"> [ {{ $phc_left_sum }} ]</span>
                </th>
                <th>
                  {{ $allocated_sum }}
                </th>
            </tr>
            
        </table>

      </div>

    </main><!-- /.container -->

<!-- Contents Ends Here -->

</x-layout-dashboard>
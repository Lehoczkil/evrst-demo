<script setup lang="ts">
import { useForm } from 'vue-formify';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import RadioButton from 'primevue/radiobutton';
import Checkbox from 'primevue/checkbox';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import {
  MemberApplicationRequests,
  type MemberApplicationPayload,
} from '@/services/requests/MemberApplicationRequests';

/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const { t } = useI18n();
const toast = useToast();

const EDUCATION_OPTIONS = ['BSc', 'MSc', 'PhD'] as const;
const LANGUAGE_OPTIONS = ['Hungarian', 'English', 'German'] as const;
const DEPARTMENT_OPTIONS = [
  'Marketing & Design',
  'Electronics & Software Development',
  'Propulsion',
  'Structure & Aerodynamics',
  'Management',
] as const;

interface FormState {
  email: string;
  name: string;
  university: string;
  education: string;
  faculty: string;
  why: string;
  hours: string;
  languages: string[];
  otherLanguage: string;
  department: string;
  tasks: string;
  skills: string;
}

const initialState: FormState = {
  email: '',
  name: '',
  university: '',
  education: '',
  faculty: '',
  why: '',
  hours: '',
  languages: [],
  otherLanguage: '',
  department: '',
  tasks: '',
  skills: '',
};

const { Form, values, reset } = useForm<FormState>({ initialValues: initialState });
const status = ref<'idle' | 'submitting' | 'success' | 'error'>('idle');

const plusKeys = [
  'join.plus.english',
  'join.plus.knowledge',
  'join.plus.hours',
  'join.plus.inPerson',
  'join.plus.tools',
  'join.plus.tdk',
] as const;
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
const onSubmit = async () => {
  status.value = 'submitting';

  const otherLanguage = (values.value.otherLanguage ?? '').trim();
  const languages = [
    ...(values.value.languages ?? []),
    ...(otherLanguage ? [`Other: ${otherLanguage}`] : []),
  ];

  const payload: MemberApplicationPayload = {
    email: values.value.email ?? '',
    name: values.value.name ?? '',
    university: values.value.university ?? '',
    education: values.value.education ?? '',
    faculty: values.value.faculty ?? '',
    why: values.value.why ?? '',
    hours: values.value.hours ?? '',
    languages,
    department: values.value.department ?? '',
    tasks: values.value.tasks ?? '',
    skills: values.value.skills ?? '',
  };

  try {
    await MemberApplicationRequests.submit(payload);
    status.value = 'success';
    window.scrollTo({ top: 0, behavior: 'smooth' });
  } catch {
    status.value = 'error';
    toast.add({
      severity: 'error',
      summary: t('join.error'),
      life: 4000,
    });
  }
};

const resetForm = () => {
  reset();
  status.value = 'idle';
};
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
const submitting = computed(() => status.value === 'submitting');
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <Toast />
  <HtmlTitle :title="t('join.title')" />

  <div
    class="container pt-[calc(var(--header-height)+32px)] pb-64px"
  >
    <div class="mb-32px">
      <SectionButton to="/">{{ t('button.home') }}</SectionButton>
    </div>

    <template v-if="status === 'success'">
      <div
        class="p-[clamp(32px,4vw,56px)] border border-cardBorder rounded-12px bg-cardBg text-center"
      >
        <h2
          class="m-0 mb-16px fs-[clamp(28px,5vw,40px)] uppercase"
        >
          {{ t('join.success.title') }}
        </h2>
        <p
          class="max-w-520px mx-auto mb-24px text-[var(--color-dimmed)] fs-16px lh-[1.6]"
        >
          {{ t('join.success.body') }}
        </p>
        <SectionButton type="button" @click="resetForm">
          {{ t('join.success.again') }}
        </SectionButton>
      </div>
    </template>

    <template v-else>
      <div
        class="relative p-[clamp(28px,4vw,56px)] mb-32px border border-cardBorder rounded-12px bg-cardBg overflow-hidden"
      >
        <div
          class="grid grid-cols-1 gap-32px md:(grid-cols-[minmax(0,1.5fr)_minmax(260px,1fr)] items-end)"
        >
          <div>
            <span
              class="inline-block py-4px px-12px mb-16px rounded-full text-primary bg-[rgb(242_172_60_/_18%)] font-600 fs-12px uppercase ls-[0.08em]"
            >
              {{ t('join.tagline') }}
            </span>
            <h1
              class="m-0 mb-16px text-primary fs-[clamp(40px,8vw,84px)] lh-[0.96] font-[var(--font-family-headline)] uppercase"
            >
              {{ t('join.title') }}
            </h1>
            <p
              class="max-w-[56ch] text-[var(--color-dimmed)] fs-17px lh-[1.6]"
            >
              {{ t('join.intro') }}
            </p>
          </div>
          <div
            class="p-20px border border-cardBorder rounded-8px bg-[rgb(255_255_255_/_4%)]"
          >
            <div
              class="mb-12px text-primary font-600 fs-12px uppercase ls-[0.08em]"
            >
              {{ t('join.plus.heading') }}
            </div>
            <ul class="flex flex-col gap-10px p-0 m-0 list-none">
              <li
                v-for="key in plusKeys"
                :key="key"
                class="flex items-start gap-10px fs-13px lh-[1.45]"
              >
                <span
                  class="inline-flex items-center justify-center shrink-0 w-18px h-18px mt-1px rounded-full text-primary bg-[rgb(242_172_60_/_22%)] fs-11px"
                >
                  ✓
                </span>
                <span>{{ t(key) }}</span>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <Form @submit="onSubmit">
        <div
          class="p-[clamp(24px,3vw,40px)] mb-32px border border-cardBorder rounded-12px bg-cardBg"
        >
          <div
            class="flex items-baseline gap-16px pb-16px mb-24px border-b border-dashed border-cardBorder"
          >
            <span
              class="join__section-num font-500 fs-32px lh-1"
            >01</span>
            <h2
              class="m-0 font-600 fs-[clamp(20px,3vw,28px)] lh-1 uppercase"
            >
              {{ t('join.section.about') }}
            </h2>
          </div>
          <div class="flex flex-col gap-16px">
            <div class="join__field flex flex-col gap-6px">
              <label>{{ t('join.email') }}</label>
              <InputText v-model="values.email" type="email" required class="w-full" />
            </div>
            <div class="grid grid-cols-1 gap-16px sm:grid-cols-2">
              <div class="join__field flex flex-col gap-6px">
                <label>{{ t('join.name') }}</label>
                <InputText v-model="values.name" required class="w-full" />
              </div>
              <div class="join__field flex flex-col gap-6px">
                <label>{{ t('join.university') }}</label>
                <InputText v-model="values.university" required class="w-full" />
              </div>
            </div>
            <div class="join__field flex flex-col gap-6px">
              <label>{{ t('join.education') }}</label>
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-8px mt-8px">
                <label
                  v-for="opt in EDUCATION_OPTIONS"
                  :key="opt"
                  class="join__option-pill flex items-center gap-10px py-10px px-14px border border-cardBorder rounded-8px bg-[rgb(255_255_255_/_2%)] cursor-pointer transition-[border-color,background-color] duration-150"
                  :data-checked="values.education === opt"
                >
                  <RadioButton v-model="values.education" :value="opt" />
                  <span>{{ opt }}</span>
                </label>
              </div>
            </div>
            <div class="join__field flex flex-col gap-6px">
              <label>{{ t('join.faculty') }}</label>
              <InputText v-model="values.faculty" required class="w-full" />
            </div>
          </div>
        </div>

        <div
          class="p-[clamp(24px,3vw,40px)] mb-32px border border-cardBorder rounded-12px bg-cardBg"
        >
          <div
            class="flex items-baseline gap-16px pb-16px mb-24px border-b border-dashed border-cardBorder"
          >
            <span
              class="join__section-num font-500 fs-32px lh-1"
            >02</span>
            <h2
              class="m-0 font-600 fs-[clamp(20px,3vw,28px)] lh-1 uppercase"
            >
              {{ t('join.section.availability') }}
            </h2>
          </div>
          <div class="flex flex-col gap-16px">
            <div class="join__field flex flex-col gap-6px">
              <label>{{ t('join.why') }}</label>
              <Textarea v-model="values.why" rows="3" auto-resize required class="w-full" />
            </div>
            <div class="join__field flex flex-col gap-6px">
              <label>{{ t('join.hours') }}</label>
              <small>{{ t('join.hoursHelp') }}</small>
              <InputText v-model="values.hours" required class="w-full" />
            </div>
            <div class="join__field flex flex-col gap-6px">
              <label>{{ t('join.languages') }}</label>
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-8px mt-8px">
                <label
                  v-for="opt in LANGUAGE_OPTIONS"
                  :key="opt"
                  class="join__option-pill flex items-center gap-10px py-10px px-14px border border-cardBorder rounded-8px bg-[rgb(255_255_255_/_2%)] cursor-pointer transition-[border-color,background-color] duration-150"
                  :data-checked="(values.languages ?? []).includes(opt)"
                >
                  <Checkbox v-model="values.languages" :value="opt" />
                  <span>{{ opt }}</span>
                </label>
              </div>
            </div>
            <div class="join__field flex flex-col gap-6px">
              <label>{{ t('join.languagesOther') }}</label>
              <InputText
                v-model="values.otherLanguage"
                :placeholder="t('join.languagesOtherPlaceholder')"
                class="w-full"
              />
            </div>
          </div>
        </div>

        <div
          class="p-[clamp(24px,3vw,40px)] mb-32px border border-cardBorder rounded-12px bg-cardBg"
        >
          <div
            class="flex items-baseline gap-16px pb-16px mb-24px border-b border-dashed border-cardBorder"
          >
            <span
              class="join__section-num font-500 fs-32px lh-1"
            >03</span>
            <h2
              class="m-0 font-600 fs-[clamp(20px,3vw,28px)] lh-1 uppercase"
            >
              {{ t('join.section.contribution') }}
            </h2>
          </div>
          <div class="flex flex-col gap-16px">
            <div class="join__field flex flex-col gap-6px">
              <label>{{ t('join.department') }}</label>
              <div class="grid grid-cols-1 gap-8px mt-8px">
                <label
                  v-for="opt in DEPARTMENT_OPTIONS"
                  :key="opt"
                  class="join__option-pill flex items-center gap-10px py-10px px-14px border border-cardBorder rounded-8px bg-[rgb(255_255_255_/_2%)] cursor-pointer transition-[border-color,background-color] duration-150"
                  :data-checked="values.department === opt"
                >
                  <RadioButton v-model="values.department" :value="opt" />
                  <span>{{ opt }}</span>
                </label>
              </div>
            </div>
            <div class="join__field flex flex-col gap-6px">
              <label>{{ t('join.tasks') }}</label>
              <Textarea v-model="values.tasks" rows="3" auto-resize required class="w-full" />
            </div>
            <div class="join__field flex flex-col gap-6px">
              <label>{{ t('join.skills') }}</label>
              <Textarea v-model="values.skills" rows="3" auto-resize required class="w-full" />
            </div>
          </div>
        </div>

        <div
          class="flex flex-col gap-16px mt-8px sm:(flex-row items-center justify-between)"
        >
          <SectionButton to="/">{{ t('button.home') }}</SectionButton>
          <SectionButton type="submit" :disabled="submitting">
            {{ submitting ? t('join.submitting') : t('join.submit') }}
          </SectionButton>
        </div>
      </Form>
    </template>
  </div>
</template>

<style lang="scss" scoped>
.join__section-num {
  color: transparent;
  background: linear-gradient(to right, transparent, var(--color-primary));
  background-clip: text;
}

.join__field {
  label {
    font-weight: 500;
    font-size: 14px;
  }

  small {
    color: var(--color-dimmed);
    font-size: 12px;
  }
}

.join__option-pill {
  &:hover {
    border-color: rgb(242 172 60 / 60%);
  }

  &[data-checked='true'] {
    border-color: var(--color-primary);
    background: rgb(242 172 60 / 12%);
  }
}
</style>
